<?php

namespace App\Models;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductFlag;
use App\Enums\ProductStatus;
use App\Enums\VendorStatus;
use App\Models\Concerns\GeneratesSlug;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;

/**
 * A product. vendor_id NULL means an in-house (admin) product.
 * Variants (size / color) will live in a later product_variants table that points here.
 */
class Product extends Model
{
    use GeneratesSlug, HasFactory, SoftDeletes;

    public const THUMBNAIL_FOLDER = 'products/thumbnails';

    public const GALLERY_FOLDER = 'products/gallery';

    public const IMAGE_HINT = 'Square images look best (recommended 800 x 800 px). JPG, PNG or WEBP, up to 2 MB.';

    /**
     * Ownership (vendor_id, created_by), the thumbnail path, approval_status, rejection_reason and the
     * home section flags are written only by the product Actions and Services.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'sub_category_id',
        'child_category_id',
        'brand_id',
        'short_description',
        'long_description',
        'price',
        'offer_price',
        'offer_start_date',
        'offer_end_date',
        'stock_qty',
        'video_url',
        'seo_title',
        'seo_description',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'offer_start_date' => 'date',
        'offer_end_date' => 'date',
        'stock_qty' => 'integer',
        'has_variants' => 'boolean',
        'status' => ProductStatus::class,
        'approval_status' => ProductApprovalStatus::class,
        'is_featured' => 'boolean',
        'is_top' => 'boolean',
        'is_best' => 'boolean',
    ];

    protected function slugScope(): ?array
    {
        return null;
    }

    /* ---------- Relations ---------- */

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function childCategory(): BelongsTo
    {
        return $this->belongsTo(ChildCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The first gallery image: the card's hover picture.
     */
    public function firstImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderByDesc('is_default')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('product_variants.is_active', true);
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(ProductApprovalLog::class)->latest('id');
    }

    /* ---------- Scopes ---------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('products.status', ProductStatus::Active->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('products.approval_status', ProductApprovalStatus::Approved->value);
    }

    public function scopeInHouse(Builder $query): Builder
    {
        return $query->whereNull('products.vendor_id');
    }

    /**
     * What shoppers may see: Active + Approved, in-house or from an approved vendor, and every
     * category level it is filed under is active (and not deleted).
     */
    public function scopeVisibleOnStorefront(Builder $query): Builder
    {
        return $query->active()->approved()
            ->where(fn (Builder $q) => $q
                ->whereNull('products.vendor_id')
                ->orWhereHas('vendor', fn (Builder $v) => $v->where('status', VendorStatus::Approved->value)))
            ->whereHas('category', fn (Builder $c) => $c->where('is_active', true))
            ->where(fn (Builder $q) => $q
                ->whereNull('products.sub_category_id')
                ->orWhereHas('subCategory', fn (Builder $s) => $s->where('is_active', true)))
            ->where(fn (Builder $q) => $q
                ->whereNull('products.child_category_id')
                ->orWhereHas('childCategory', fn (Builder $c) => $c->where('is_active', true)));
    }

    /**
     * Products whose offer applies today (offer price set, and today inside the dates when they are set).
     */
    public function scopeWithValidOffer(Builder $query): Builder
    {
        $today = today()->toDateString();

        return $query->whereNotNull('products.offer_price')
            ->where(fn (Builder $q) => $q->whereNull('products.offer_start_date')->orWhereDate('products.offer_start_date', '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull('products.offer_end_date')->orWhereDate('products.offer_end_date', '>=', $today));
    }

    public function scopeFlagged(Builder $query, string $flag): Builder
    {
        return $query->where('products.'.$flag, true);
    }

    /**
     * Relations every product card needs, in one go.
     */
    public function scopeForCards(Builder $query): Builder
    {
        return $query->with(['category:id,name,slug', 'firstImage', 'activeVariants']);
    }

    /* ---------- Helpers & accessors ---------- */

    public function isInHouse(): bool
    {
        return $this->vendor_id === null;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === ProductApprovalStatus::Approved;
    }

    /**
     * Home section flags that are switched on.
     *
     * @return array<int, ProductFlag>
     */
    public function activeFlags(): array
    {
        return array_values(array_filter(ProductFlag::cases(), fn (ProductFlag $flag) => (bool) $this->{$flag->value}));
    }

    /**
     * "Category › Sub category › Child category" (only the levels that are set).
     */
    public function categoryTrail(): string
    {
        return collect([$this->category?->name, $this->subCategory?->name, $this->childCategory?->name])->filter()->implode(' › ');
    }

    /**
     * Price to show in tables: one price, or "low – high" when the variants are priced differently.
     */
    public function priceRangeLabel(): string
    {
        if (! $this->has_variants) {
            return self::money((float) $this->current_price);
        }

        $prices = $this->sellableVariants()->map(fn (ProductVariant $v) => (float) $v->current_price);

        if ($prices->isEmpty()) {
            return self::money((float) $this->price);
        }

        return $prices->min() === $prices->max()
            ? self::money($prices->min())
            : self::money($prices->min()).' – '.self::money($prices->max());
    }

    /**
     * Everything the product page needs to switch variants in the browser without asking the server
     * (embedded as JSON): the active variants with their option value ids, prices, stock and image.
     *
     * @return array<int, array<string, mixed>>
     */
    public function variantData(): array
    {
        return $this->sellableVariants()->map(fn (ProductVariant $variant) => [
            'id' => $variant->id,
            'values' => $variant->values->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'sku' => $variant->sku,
            // Numbers for the live total (price × quantity) in the browser.
            'current_price' => (float) $variant->current_price,
            'current_price_label' => $variant->current_price_label,
            'price_label' => $variant->price_label,
            'discount_percent' => $variant->discount_percent,
            'stock_qty' => $variant->stock_qty,
            'in_stock' => $variant->in_stock,
            'image' => $variant->hasImage() ? $variant->image_url : null,
            'is_default' => $variant->is_default,
        ])->values()->all();
    }

    public function hasThumbnail(): bool
    {
        return $this->thumbnail && Storage::disk('public')->exists($this->thumbnail);
    }

    /**
     * Whether today is inside the offer dates (no dates: always open). Variant offer prices follow this window too.
     */
    public function offerWindowIsOpen(): bool
    {
        $today = today();

        return ($this->offer_start_date === null || $this->offer_start_date->lte($today))
            && ($this->offer_end_date === null || $this->offer_end_date->gte($today));
    }

    public function hasValidOffer(): bool
    {
        if (! $this->offerWindowIsOpen()) {
            return false;
        }

        return $this->has_variants
            ? $this->sellableVariants()->contains(fn (ProductVariant $v) => $v->offer_price !== null)
            : $this->offer_price !== null;
    }

    /**
     * The active variants, each pointing back at this product (they read its offer dates and thumbnail).
     *
     * @return Collection<int, ProductVariant>
     */
    public function sellableVariants(): Collection
    {
        return $this->activeVariants->each(fn (ProductVariant $variant) => $variant->setRelation('product', $this));
    }

    /**
     * The variant a shopper sees first: the default one when it is active, else the first active one.
     */
    public function defaultVariant(): ?ProductVariant
    {
        $variants = $this->sellableVariants();

        return $variants->firstWhere('is_default', true) ?? $variants->first();
    }

    /**
     * The active variant with the lowest price today (ties go to the default variant): its price, offer
     * price and discount are what lists and cards show.
     */
    public function pricingVariant(): ?ProductVariant
    {
        return $this->sellableVariants()
            ->sortBy(fn (ProductVariant $v) => [(float) $v->current_price, $v->is_default ? 0 : 1, $v->id])
            ->first();
    }

    /**
     * The attributes the product's variants use, each with the values that appear, in display order.
     *
     * @return SupportCollection<int, array{attribute: \App\Models\Attribute, values: SupportCollection<int, AttributeValue>}>
     */
    public function usedAttributes(): SupportCollection
    {
        $variants = $this->relationLoaded('variants') ? $this->variants->where('is_active', true) : $this->activeVariants()->with('values.attribute')->get();

        return $variants->flatMap->values
            ->unique('id')
            ->groupBy('attribute_id')
            ->map(fn (SupportCollection $values) => [
                'attribute' => $values->first()->attribute,
                'values' => $values->sortBy(['sort_order', 'id'])->values(),
            ])
            ->sortBy(fn (array $row) => [$row['attribute']->sort_order, $row['attribute']->id])
            ->values();
    }

    /**
     * Uploaded thumbnail, or a neutral placeholder when the file is missing.
     * asset() follows the host being browsed, so it does not depend on APP_URL.
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasThumbnail()
            ? asset('storage/'.$this->thumbnail)
            : asset('frontend-assets/images/product-placeholder.svg'));
    }

    /**
     * Second picture of the product card (shown on hover): the first gallery image, else the thumbnail.
     */
    protected function hoverImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->firstImage?->url ?? $this->thumbnail_url);
    }

    /**
     * The price a shopper pays today: the offer price only while the offer is valid.
     */
    protected function currentPrice(): Attribute
    {
        return Attribute::get(function () {
            if ($this->has_variants && $variant = $this->pricingVariant()) {
                return $variant->current_price;
            }

            return $this->hasValidOffer() ? $this->offer_price : $this->price;
        });
    }

    /**
     * Whole-number discount of a valid offer, e.g. 20 for 20 % off; 0 when there is none.
     */
    protected function discountPercent(): Attribute
    {
        return Attribute::get(function () {
            if ($this->has_variants) {
                return $this->pricingVariant()?->discount_percent ?? 0;
            }

            if (! $this->hasValidOffer() || (float) $this->price <= 0) {
                return 0;
            }

            return (int) round(((float) $this->price - (float) $this->offer_price) / (float) $this->price * 100);
        });
    }

    /**
     * Regular price with the currency symbol, e.g. "$200.00".
     */
    protected function priceLabel(): Attribute
    {
        return Attribute::get(fn () => self::money((float) ($this->has_variants ? ($this->pricingVariant()?->price ?? $this->price) : $this->price)));
    }

    /**
     * Price to pay today with the currency symbol; "From ₹X" when the variants are priced differently.
     */
    protected function currentPriceLabel(): Attribute
    {
        return Attribute::get(fn () => ($this->has_price_range ? 'From ' : '').self::money((float) $this->current_price));
    }

    /**
     * True when the active variants do not all cost the same today.
     */
    protected function hasPriceRange(): Attribute
    {
        return Attribute::get(fn () => $this->has_variants
            && $this->sellableVariants()->map(fn (ProductVariant $v) => (float) $v->current_price)->unique()->count() > 1);
    }

    /**
     * Units in stock: the product's own stock, or the sum over its active variants.
     */
    protected function totalStock(): Attribute
    {
        return Attribute::get(fn () => $this->has_variants
            ? (int) $this->sellableVariants()->sum('stock_qty')
            : (int) $this->stock_qty);
    }

    public static function money(float $amount): string
    {
        return config('marketplace.currency_symbol', '$').number_format($amount, 2);
    }

    protected function inStock(): Attribute
    {
        return Attribute::get(fn () => $this->has_variants
            ? $this->sellableVariants()->contains(fn (ProductVariant $v) => $v->stock_qty > 0)
            : $this->stock_qty > 0);
    }

    protected function isNew(): Attribute
    {
        return Attribute::get(fn () => $this->created_at !== null
            && $this->created_at->gte(now()->subDays((int) config('marketplace.new_badge_days', 14))));
    }

    /**
     * When the offer ends (end of its last day), for countdowns; null without a dated, valid offer.
     */
    protected function offerEndsAt(): Attribute
    {
        return Attribute::get(fn (): ?CarbonInterface => $this->hasValidOffer() && $this->offer_end_date
            ? $this->offer_end_date->copy()->endOfDay()
            : null);
    }
}
