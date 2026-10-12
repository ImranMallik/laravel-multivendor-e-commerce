<?php

namespace App\Services;

use App\Data\ProductData;
use App\Enums\ProductApprovalStatus;
use App\Enums\ProductFlag;
use App\Enums\ProductStatus;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Creates, updates and deletes products for BOTH panels. The acting person decides the rules:
 * an Admin may set flags and the owner and creates approved products; a vendor User always works
 * on their own shop, never sets flags, and their changes go through approval when enabled.
 */
class ProductService
{
    /**
     * Fields whose change on an approved vendor product needs a new review.
     */
    public const REVIEWED_FIELDS = [
        'name', 'price', 'offer_price', 'short_description', 'long_description',
        'category_id', 'sub_category_id', 'child_category_id',
    ];

    public function __construct(
        private readonly ImageUploadService $images,
        private readonly HtmlSanitizer $sanitizer,
        private readonly ProductApprovalService $approval,
        private readonly ProductVariantService $variants,
    ) {}

    public function create(ProductData $data, Admin|User $actor): Product
    {
        $vendorId = $this->vendorIdFor($actor, $data);
        $attributes = $this->clean($data->attributes);

        $thumbnail = $data->thumbnail ? $this->images->upload($data->thumbnail, Product::THUMBNAIL_FOLDER) : null;
        $gallery = $this->uploadGallery($data->gallery);

        if (! $thumbnail) {
            throw new DomainException('A thumbnail is required.');
        }

        try {
            $variantImages = $data->hasVariants ? $this->variants->uploadImages($data->variants) : [];
        } catch (Throwable $e) {
            $this->images->delete($thumbnail);
            array_map(fn (string $path) => $this->images->delete($path), $gallery);

            throw $e;
        }

        try {
            return DB::transaction(function () use ($data, $actor, $vendorId, $attributes, $thumbnail, $gallery, $variantImages) {
                $product = new Product($attributes);
                $product->has_variants = $data->hasVariants;
                // With variants the product's own price and stock are a mirror (filled in after the variants are saved).
                $product->price ??= 0;
                $product->stock_qty ??= 0;
                $product->vendor_id = $vendorId;
                $product->createdBy()->associate($actor);
                $product->thumbnail = $thumbnail;

                if ($this->isVendor($actor)) {
                    $needsReview = (bool) config('marketplace.vendor_product_approval', true);
                    $product->approval_status = $needsReview ? ProductApprovalStatus::Pending : ProductApprovalStatus::Approved;
                } else {
                    $product->approval_status = ProductApprovalStatus::Approved;
                    $product->forceFill($data->flags ?? []);
                }

                $product->save();

                $this->storeGallery($product, $gallery, 0);
                $this->syncSpecifications($product, $data->specifications);

                if ($data->hasVariants) {
                    $this->variants->sync($product, $data->variants, $variantImages);
                    $this->variants->mirror($product);
                }

                $this->approval->logCreated(
                    $product,
                    $actor instanceof Admin ? $actor : null,
                    match (true) {
                        $actor instanceof Admin => 'Created by admin',
                        $product->isApproved() => 'Created by vendor (approval not required)',
                        default => 'Submitted by vendor',
                    },
                );

                return $product;
            });
        } catch (Throwable $e) {
            $this->images->delete($thumbnail);
            array_map(fn (string $path) => $this->images->delete($path), $gallery);
            $this->variants->discard($variantImages);

            throw $e;
        }
    }

    public function update(Product $product, ProductData $data, Admin|User $actor): Product
    {
        $attributes = $this->clean($data->attributes);
        $oldThumbnail = $product->thumbnail;

        $this->assertGalleryRoom($product, count($data->gallery));

        $thumbnail = $data->thumbnail ? $this->images->upload($data->thumbnail, Product::THUMBNAIL_FOLDER) : null;
        $gallery = $this->uploadGallery($data->gallery);

        try {
            $variantImages = $data->hasVariants ? $this->variants->uploadImages($data->variants) : [];
        } catch (Throwable $e) {
            $this->images->delete($thumbnail);
            array_map(fn (string $path) => $this->images->delete($path), $gallery);

            throw $e;
        }

        $replacedVariantImages = [];

        try {
            DB::transaction(function () use ($product, $data, $actor, $attributes, $thumbnail, $gallery, $variantImages, &$replacedVariantImages) {
                $product->fill($attributes);
                $product->has_variants = $data->hasVariants;

                if ($thumbnail) {
                    $product->thumbnail = $thumbnail;
                }

                if ($actor instanceof Admin) {
                    $product->forceFill($data->flags ?? []);

                    if ($data->assignVendor) {
                        $product->vendor_id = $data->vendorId;
                    }
                }

                $mediaChanged = $thumbnail !== null || $gallery !== [];
                // Switching variants on or off changes what the product sells, so it is reviewed like a price change.
                $reviewedFieldsChanged = $product->isDirty([...self::REVIEWED_FIELDS, 'has_variants']) || $mediaChanged;

                $product->save();

                $this->reorderGallery($product, $data->galleryOrder);
                $this->storeGallery($product, $gallery, (int) $product->images()->max('sort_order') + 1);
                $this->syncSpecifications($product, $data->specifications);

                if ($data->hasVariants) {
                    $result = $this->variants->sync($product, $data->variants, $variantImages);
                    $replacedVariantImages = $result['replaced'];
                    $reviewedFieldsChanged = $reviewedFieldsChanged || $result['reviewed'];
                    $this->variants->mirror($product);
                }

                if ($this->isVendor($actor)) {
                    $this->afterVendorChange($product, $reviewedFieldsChanged);
                }
            });
        } catch (Throwable $e) {
            $this->images->delete($thumbnail);
            array_map(fn (string $path) => $this->images->delete($path), $gallery);
            $this->variants->discard($variantImages);

            throw $e;
        }

        // The old thumbnail and replaced variant images go only after the new data is saved.
        if ($thumbnail) {
            $this->images->delete($oldThumbnail);
        }

        $this->variants->discard($replacedVariantImages);

        return $product->refresh();
    }

    /**
     * Soft delete. Files stay so the product could be restored; ProductObserver removes them on force delete.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function deleteImage(ProductImage $image, Admin|User $actor): void
    {
        $product = $image->product;
        $path = $image->image;

        DB::transaction(function () use ($image, $product, $actor) {
            $image->delete();

            if ($this->isVendor($actor)) {
                $this->afterVendorChange($product, true);
            }
        });

        $this->images->delete($path);
    }

    /**
     * Active <-> Inactive (a draft becomes active). Vendors may switch only approved products.
     */
    public function toggleStatus(Product $product, Admin|User $actor): Product
    {
        if ($this->isVendor($actor) && ! $product->isApproved()) {
            throw new DomainException('This product can be activated once it is approved.');
        }

        $product->status = $product->status === ProductStatus::Active ? ProductStatus::Inactive : ProductStatus::Active;
        $product->save();

        return $product;
    }

    /**
     * Home section flags are admin only (the routes live in the admin panel).
     */
    public function toggleFlag(Product $product, ProductFlag $flag): Product
    {
        $product->{$flag->value} = ! $product->{$flag->value};
        $product->save();

        return $product;
    }

    /* ---------- internals ---------- */

    private function isVendor(Admin|User $actor): bool
    {
        return $actor instanceof User;
    }

    /**
     * Vendors always create for their own shop; admins choose (null = in-house).
     */
    private function vendorIdFor(Admin|User $actor, ProductData $data): ?int
    {
        if ($actor instanceof User) {
            $vendor = $actor->vendor;

            if (! $vendor?->isApproved()) {
                throw new DomainException('Only approved sellers can add products.');
            }

            return $vendor->id;
        }

        return $data->assignVendor ? $data->vendorId : null;
    }

    /**
     * A vendor edit of a rejected product resubmits it; a change to reviewed fields of an approved
     * product sends it back to review (when approval is enabled).
     */
    private function afterVendorChange(Product $product, bool $reviewedFieldsChanged): void
    {
        if ($product->approval_status === ProductApprovalStatus::Rejected) {
            $this->approval->markPending($product, 'Resubmitted by vendor');

            return;
        }

        if ($reviewedFieldsChanged && $product->isApproved() && config('marketplace.vendor_product_approval', true)) {
            $this->approval->markPending($product, 'Changed by vendor after approval');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function clean(array $attributes): array
    {
        if (array_key_exists('long_description', $attributes)) {
            $attributes['long_description'] = $this->sanitizer->clean($attributes['long_description']);
        }

        // Without an offer price the dates mean nothing.
        if (array_key_exists('offer_price', $attributes) && $attributes['offer_price'] === null) {
            $attributes['offer_start_date'] = null;
            $attributes['offer_end_date'] = null;
        }

        return $attributes;
    }

    private function assertGalleryRoom(Product $product, int $adding): void
    {
        $max = (int) config('marketplace.max_gallery_images', 8);

        if ($adding > 0 && $product->images()->count() + $adding > $max) {
            throw new DomainException("A product can have at most {$max} gallery images.");
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    private function uploadGallery(array $files): array
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                $paths[] = $this->images->upload($file, Product::GALLERY_FOLDER);
            }
        } catch (Throwable $e) {
            array_map(fn (string $path) => $this->images->delete($path), $paths);

            throw $e;
        }

        return $paths;
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function storeGallery(Product $product, array $paths, int $startAt): void
    {
        foreach (array_values($paths) as $index => $path) {
            $product->images()->create(['image' => $path, 'sort_order' => $startAt + $index]);
        }
    }

    /**
     * @param  array<int, int>|null  $order  ids of this product's images; unknown ids are ignored
     */
    private function reorderGallery(Product $product, ?array $order): void
    {
        if (! $order) {
            return;
        }

        $ids = $product->images()->pluck('id')->all();
        $position = 0;

        foreach ($order as $id) {
            if (in_array((int) $id, $ids, true)) {
                ProductImage::whereKey($id)->update(['sort_order' => $position++]);
            }
        }
    }

    /**
     * @param  array<int, array{key: string, value: string}>  $rows
     */
    private function syncSpecifications(Product $product, array $rows): void
    {
        $product->specifications()->delete();

        foreach (array_values($rows) as $index => $row) {
            $product->specifications()->create(['key' => $row['key'], 'value' => $row['value'], 'sort_order' => $index]);
        }
    }
}
