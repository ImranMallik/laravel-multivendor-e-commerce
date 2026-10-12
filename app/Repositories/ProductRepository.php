<?php

namespace App\Repositories;

use App\Enums\ProductFlag;
use App\Models\Category;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront product queries. The home sections are cached until the end of the day (offers and the
 * "New" badge depend on the date) and flushed by ProductObserver whenever a product, its vendor or
 * a category changes.
 */
class ProductRepository
{
    public const HOME_CACHE_KEY = 'frontend.home_products';

    /**
     * @return array{
     *     flash: Collection<int, Product>,
     *     flash_ends_at: CarbonInterface|null,
     *     top: Collection<int, Product>,
     *     top_categories: \Illuminate\Support\Collection<int, Category>,
     *     featured_categories: \Illuminate\Support\Collection<int, Category>,
     *     hot_offers: Collection<int, Product>,
     *     featured: Collection<int, Product>,
     *     category_sections: array<int, array{category: Category, products: Collection<int, Product>}>,
     *     best: Collection<int, Product>,
     *     newest: Collection<int, Product>
     * }
     */
    public function homeSections(): array
    {
        return Cache::remember(self::HOME_CACHE_KEY, now()->endOfDay(), fn () => $this->buildHomeSections());
    }

    public function flush(): void
    {
        Cache::forget(self::HOME_CACHE_KEY);
    }

    /**
     * @return Builder<Product>
     */
    public function visible(): Builder
    {
        return Product::query()->visibleOnStorefront()->forCards();
    }

    /**
     * Visible products for listing pages, newest first.
     * TODO: sorting, price / brand filters and the list view when the shop page is built.
     *
     * @param  array<string, int>  $filter  column => value, e.g. ['category_id' => 3] or ['vendor_id' => 7]
     * @return LengthAwarePaginator<Product>
     */
    public function paginate(array $filter = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->visible();

        foreach ($filter as $column => $value) {
            $query->where('products.'.$column, $value);
        }

        return $query->latest('products.id')->paginate($perPage)->withQueryString();
    }

    /**
     * A product page: 404 unless the product is visible on the storefront.
     */
    public function findVisibleBySlug(string $slug): Product
    {
        $product = Product::query()
            ->visibleOnStorefront()
            ->where('slug', $slug)
            ->with(['category', 'subCategory', 'childCategory', 'brand', 'images', 'specifications', 'vendor.user', 'variants.values.attribute'])
            ->firstOrFail();

        // The page already has every variant; reuse them instead of asking the database for the active ones again.
        $product->setRelation('activeVariants', $product->variants->where('is_active', true)->values());

        return $product;
    }

    /**
     * Other visible products of the same category.
     *
     * @return Collection<int, Product>
     */
    public function related(Product $product, int $limit = 8): Collection
    {
        return $this->visible()
            ->where('products.category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->latest('products.id')
            ->limit($limit)
            ->get();
    }

    private function buildHomeSections(): array
    {
        $limit = (int) config('marketplace.home_section_limit', 12);

        // Flash sale: dated offers that are running today, ending soonest first.
        $flash = $this->visible()->withValidOffer()->whereNotNull('offer_end_date')
            ->orderBy('offer_end_date')->latest('id')->limit($limit)->get();

        $featured = $this->visible()->flagged(ProductFlag::Featured->value)->latest('id')->limit($limit)->get();

        $categorySections = Category::active()
            ->whereIn('slug', (array) config('marketplace.home_category_sections', []))
            ->get(['id', 'name', 'slug'])
            ->sortBy(fn (Category $category) => array_search($category->slug, (array) config('marketplace.home_category_sections', []), true))
            ->map(fn (Category $category) => [
                'category' => $category,
                'products' => $this->visible()->where('products.category_id', $category->id)->latest('id')->limit($limit)->get(),
            ])
            ->values()
            ->all();

        $top = $this->visible()->flagged(ProductFlag::Top->value)->latest('id')->limit($limit)->get();

        return [
            'flash' => $flash,
            'flash_ends_at' => $flash->first()?->offer_ends_at,
            'top' => $top,
            // Filter tabs: the categories of the listed products, in first-seen order.
            'top_categories' => $top->pluck('category')->filter()->unique('id')->values(),
            'featured_categories' => $featured->pluck('category')->filter()->unique('id')->values(),
            // The three large "hot deal" cards: featured products with a running offer.
            'hot_offers' => $featured->filter(fn (Product $product) => $product->hasValidOffer())->take(3)->values(),
            'featured' => $featured,
            'category_sections' => $categorySections,
            'best' => $this->visible()->flagged(ProductFlag::Best->value)->latest('id')->limit(9)->get(),
            // TODO: "weekly best rated" needs reviews; until then it shows the newest products.
            'newest' => $this->visible()->latest('id')->limit(9)->get(),
        ];
    }
}
