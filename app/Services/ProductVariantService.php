<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Saves a product's variants for BOTH panels (called by ProductService inside its transaction).
 *
 * Existing variants are matched by id and keep their combination; a combination that was removed
 * earlier is restored instead of duplicated; variants missing from the submission are soft deleted
 * (orders will reference variants, so they are never hard deleted).
 */
class ProductVariantService
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * Stores the variant images that were uploaded, before the transaction starts.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string> row position => stored path
     */
    public function uploadImages(array $rows): array
    {
        $paths = [];

        try {
            foreach ($rows as $position => $row) {
                if (($row['image'] ?? null) instanceof UploadedFile) {
                    $paths[$position] = $this->images->upload($row['image'], ProductVariant::IMAGE_FOLDER);
                }
            }
        } catch (Throwable $e) {
            $this->discard($paths);

            throw $e;
        }

        return $paths;
    }

    /**
     * Removes images stored by uploadImages() (the save failed).
     *
     * @param  array<int, string>  $paths
     */
    public function discard(array $paths): void
    {
        foreach ($paths as $path) {
            $this->images->delete($path);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows  normalized rows from ProductData::$variants
     * @param  array<int, string>  $uploaded  row position => new image path
     * @return array{reviewed: bool, replaced: array<int, string>} reviewed: prices, offers, images or combinations changed;
     *                                                             replaced: old image files to delete once the transaction has committed
     */
    public function sync(Product $product, array $rows, array $uploaded): array
    {
        $existing = $product->variants()->withTrashed()->with('values')->get();
        $byId = $existing->keyBy('id');
        $byHash = $existing->keyBy('combination_hash');

        $reviewed = false;
        $replaced = [];
        $keptIds = [];

        foreach (array_values($rows) as $position => $row) {
            $hash = ProductVariant::combinationHash($row['value_ids']);
            $variant = ($row['id'] ? $byId->get($row['id']) : null) ?? $byHash->get($hash);
            $isNew = $variant === null;

            if ($isNew) {
                $variant = new ProductVariant;
                $variant->product_id = $product->id;
                $variant->combination_hash = $hash;
                $reviewed = true;
            } elseif ($variant->trashed()) {
                $variant->restore();
                $reviewed = true;
            }

            $variant->fill([
                'sku' => $row['sku'],
                // Two decimals, like the stored value, so an unchanged price is not seen as a change.
                'price' => number_format((float) $row['price'], 2, '.', ''),
                'offer_price' => $row['offer_price'] === null ? null : number_format((float) $row['offer_price'], 2, '.', ''),
                'stock_qty' => $row['stock_qty'],
                'is_active' => $row['is_active'],
                'is_default' => $row['is_default'],
            ]);

            // A price or offer change needs a new review; stock, SKU and the switches do not.
            if (! $isNew && ($variant->isDirty('price') || $variant->isDirty('offer_price'))) {
                $reviewed = true;
            }

            $oldImage = $variant->image;

            if (isset($uploaded[$position])) {
                $variant->image = $uploaded[$position];
            } elseif ($row['remove_image']) {
                $variant->image = null;
            }

            if ($variant->isDirty('image')) {
                $reviewed = true;

                if ($oldImage) {
                    $replaced[] = $oldImage;
                }
            }

            $variant->save();

            if ($isNew) {
                $variant->values()->sync($row['value_ids']);
            }

            $keptIds[] = $variant->id;
        }

        // Variants that were not submitted any more are soft deleted.
        foreach ($existing as $variant) {
            if (! $variant->trashed() && ! in_array($variant->id, $keptIds, true)) {
                $variant->delete();
                $reviewed = true;
            }
        }

        return ['reviewed' => $reviewed, 'replaced' => $replaced];
    }

    /**
     * Keeps the product's own price, offer price and stock columns in step with its variants (the
     * cheapest active variant's prices, the total stock), so lists, sorting and the home sections
     * keep working on plain columns. The variants stay the source of truth.
     */
    public function mirror(Product $product): void
    {
        $product->unsetRelation('activeVariants');

        $pricing = $product->pricingVariant();

        $product->forceFill([
            'price' => $pricing?->price ?? 0,
            'offer_price' => $pricing?->offer_price,
            'stock_qty' => (int) $product->sellableVariants()->sum('stock_qty'),
        ])->save();
    }
}
