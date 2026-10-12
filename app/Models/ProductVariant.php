<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * One sellable combination of a product's attribute values (e.g. Red / M) with its own SKU, price,
 * stock and image. Offer dates belong to the product. Soft deleted only: orders will reference variants.
 */
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    public const IMAGE_FOLDER = 'products/variants';

    protected $fillable = [
        'sku',
        'price',
        'offer_price',
        'stock_qty',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'stock_qty' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_values')
            ->with('attribute')
            ->orderBy('attribute_values.attribute_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('product_variants.is_active', true);
    }

    /**
     * Stable hash of a set of attribute value ids (order does not matter): the unique key of a combination.
     *
     * @param  array<int, int|string>  $valueIds
     */
    public static function combinationHash(array $valueIds): string
    {
        $ids = array_map('intval', $valueIds);
        sort($ids);

        return sha1(implode('-', $ids));
    }

    public function hasImage(): bool
    {
        return $this->image && Storage::disk('public')->exists($this->image);
    }

    /**
     * Whether the product's offer window (its dates) is open today; the variant offer price applies only then.
     */
    public function hasValidOffer(): bool
    {
        return $this->offer_price !== null && $this->product?->offerWindowIsOpen() === true;
    }

    /**
     * The variant's own image, else the product thumbnail.
     */
    protected function imageUrl(): CastAttribute
    {
        return CastAttribute::get(fn () => $this->hasImage()
            ? asset('storage/'.$this->image)
            : ($this->product?->thumbnail_url ?? asset('frontend-assets/images/product-placeholder.svg')));
    }

    protected function currentPrice(): CastAttribute
    {
        return CastAttribute::get(fn () => $this->hasValidOffer() ? $this->offer_price : $this->price);
    }

    protected function discountPercent(): CastAttribute
    {
        return CastAttribute::get(function () {
            if (! $this->hasValidOffer() || (float) $this->price <= 0) {
                return 0;
            }

            return (int) round(((float) $this->price - (float) $this->offer_price) / (float) $this->price * 100);
        });
    }

    /**
     * Regular price with the currency symbol.
     */
    protected function priceLabel(): CastAttribute
    {
        return CastAttribute::get(fn () => Product::money((float) $this->price));
    }

    /**
     * Price to pay today with the currency symbol.
     */
    protected function currentPriceLabel(): CastAttribute
    {
        return CastAttribute::get(fn () => Product::money((float) $this->current_price));
    }

    protected function inStock(): CastAttribute
    {
        return CastAttribute::get(fn () => $this->is_active && $this->stock_qty > 0);
    }

    /**
     * "Red / M": the values in attribute order.
     */
    protected function label(): CastAttribute
    {
        return CastAttribute::get(fn () => $this->values
            ->sortBy(fn (AttributeValue $v) => [$v->attribute?->sort_order ?? 0, $v->attribute_id])
            ->pluck('value')
            ->implode(' / '));
    }
}
