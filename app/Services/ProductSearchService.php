<?php

namespace App\Services;

use App\Enums\ProductSort;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Support\SearchTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Storefront product search, used by the shop results page and the header's live suggestions. This is the only
 * class that knows how search works, so moving to Laravel Scout later means rewriting this class only.
 *
 * Matching (storefront-visible products only): LIKE on name, SKU, variant SKU, short description, brand,
 * category / sub / child category names and the vendor's shop name. On MySQL, terms of 3+ characters also use
 * the FULLTEXT index on name + short description (natural language mode): it finds words that are apart or out
 * of order ("red shirt" -> "Red Linen Shirt") but never partial words ("lam" -> "lamp"), so the LIKE checks stay
 * for live typing, short terms and SKUs.
 *
 * Relevance: exact SKU, then name starts with the term, then name contains it, then the other fields (ties broken
 * by the FULLTEXT score); then in stock before out of stock, then newest.
 */
class ProductSearchService
{
    public const PER_PAGE = 12;

    /** Suggestions start at this many characters (search.js waits for the same). */
    public const SUGGEST_MIN_LENGTH = 2;

    public const SUGGEST_PRODUCTS = 6;

    public const SUGGEST_CATEGORIES = 3;

    public const SUGGEST_BRANDS = 3;

    public const POPULAR_CATEGORIES = 4;

    /** Suggestions are cached per term + category; product edits show up after at most this long. */
    public const SUGGEST_CACHE_SECONDS = 600;

    public const SUGGEST_CACHE_PREFIX = 'frontend.search.suggest.';

    private const LIKE = "like ? escape '".SearchTerm::LIKE_ESCAPE."'";

    private const MATCH = 'match (products.name, products.short_description) against (? in natural language mode)';

    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryMenuService $categoryMenu,
    ) {}

    /**
     * An active top-level category for ?category=slug (its products include those of its sub / child categories).
     */
    public function findCategory(?string $slug): ?Category
    {
        return filled($slug) ? Category::query()->active()->where('slug', $slug)->first() : null;
    }

    /**
     * An active brand for ?brand=slug.
     */
    public function findBrand(?string $slug): ?Brand
    {
        return filled($slug) ? Brand::query()->active()->where('slug', $slug)->first() : null;
    }

    /**
     * Results page: visible products matching the term (all of them when it is empty), filtered and sorted.
     *
     * @return LengthAwarePaginator<Product>
     */
    public function paginate(SearchTerm $term, ProductSort $sort, ?Category $category = null, ?Brand $brand = null, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = $this->query($term, $category, $brand);
        $this->sort($query, $term, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Live suggestions: up to 6 products, 3 categories and 3 brands; popular categories when no product matches.
     * Names come split into parts with the matched words flagged, so the browser can highlight them without
     * the server sending HTML.
     *
     * @return array{term: string, products: array<int, array<string, mixed>>, categories: array<int, array<string, mixed>>, brands: array<int, array<string, mixed>>, popular: array<int, array<string, string>>, more_url: string}
     */
    public function suggestions(SearchTerm $term, ?Category $category = null): array
    {
        if ($term->length() < self::SUGGEST_MIN_LENGTH) {
            return ['term' => $term->value, 'products' => [], 'categories' => [], 'brands' => [], 'popular' => [], 'more_url' => route('shop.index')];
        }

        // The entries hold absolute URLs, so the site root (host and base path) is part of the key: a list built
        // for one host (another domain, www, the CLI's APP_URL) is never shown on another.
        $key = self::SUGGEST_CACHE_PREFIX.md5($term->normalized().'|'.($category?->id ?? '').'|'.url('/'));

        return Cache::remember($key, self::SUGGEST_CACHE_SECONDS, fn () => $this->buildSuggestions($term, $category));
    }

    /**
     * @return Builder<Product>
     */
    private function query(SearchTerm $term, ?Category $category, ?Brand $brand): Builder
    {
        return $this->products->visible()
            ->when($category, fn (Builder $q) => $q->where('products.category_id', $category->id))
            ->when($brand, fn (Builder $q) => $q->where('products.brand_id', $brand->id))
            ->when(! $term->isEmpty(), fn (Builder $q) => $q->where(fn (Builder $match) => $this->match($match, $term)));
    }

    private function match(Builder $query, SearchTerm $term): void
    {
        [$phrase, $bindings] = $this->phraseMatch($term);

        $query->whereRaw($phrase, $bindings);

        if ($this->usesFullText($term)) {
            $query->orWhereRaw(self::MATCH, [$term->value]);
        }
    }

    /**
     * SQL that is true when the whole term appears in one of the searched fields (LIKE). Written out once so the
     * same condition both filters and ranks (FULLTEXT-only matches come after it).
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private function phraseMatch(SearchTerm $term): array
    {
        $like = self::LIKE;
        $fields = [
            "products.name {$like}",
            "products.sku {$like}",
            "products.short_description {$like}",
            "exists (select 1 from product_variants where product_variants.product_id = products.id and product_variants.is_active = 1 and product_variants.deleted_at is null and product_variants.sku {$like})",
            "exists (select 1 from brands where brands.id = products.brand_id and brands.deleted_at is null and brands.name {$like})",
            "exists (select 1 from categories where categories.id = products.category_id and categories.deleted_at is null and categories.name {$like})",
            "exists (select 1 from sub_categories where sub_categories.id = products.sub_category_id and sub_categories.deleted_at is null and sub_categories.name {$like})",
            "exists (select 1 from child_categories where child_categories.id = products.child_category_id and child_categories.deleted_at is null and child_categories.name {$like})",
            "exists (select 1 from vendors where vendors.id = products.vendor_id and vendors.shop_name {$like})",
        ];

        return ['('.implode(' or ', $fields).')', array_fill(0, count($fields), $term->contains())];
    }

    private function sort(Builder $query, SearchTerm $term, ProductSort $sort): void
    {
        if ($sort === ProductSort::Relevance && ! $term->isEmpty()) {
            $this->orderByRelevance($query, $term);

            return;
        }

        match ($sort) {
            ProductSort::PriceAsc => $query->orderByRaw($this->effectivePrice().' asc', $this->effectivePriceBindings()),
            ProductSort::PriceDesc => $query->orderByRaw($this->effectivePrice().' desc', $this->effectivePriceBindings()),
            default => null,
        };

        $query->orderByDesc('products.id');
    }

    private function orderByRelevance(Builder $query, SearchTerm $term): void
    {
        $sku = Str::lower($term->value);
        $fullText = $this->usesFullText($term);
        [$phrase, $phraseBindings] = $this->phraseMatch($term);

        // 0 exact SKU, 1 name starts with the term, 2 name contains it, 3 the term is in another field,
        // 4 only the FULLTEXT index matched (some of the words).
        $query->orderByRaw(
            'case when lower(products.sku) = ?'
            .' or exists (select 1 from product_variants where product_variants.product_id = products.id'
            .' and product_variants.is_active = 1 and product_variants.deleted_at is null and lower(product_variants.sku) = ?) then 0'
            .' when products.name '.self::LIKE.' then 1'
            .' when products.name '.self::LIKE.' then 2'
            .($fullText ? ' when '.$phrase.' then 3 else 4 end' : ' else 3 end'),
            array_merge([$sku, $sku, $term->startsWith(), $term->contains()], $fullText ? $phraseBindings : []),
        );

        if ($fullText) {
            $query->orderByRaw(self::MATCH.' desc', [$term->value]);
        }

        // stock_qty mirrors the summed variant stock for products with variants.
        $query->orderByRaw('case when products.stock_qty > 0 then 0 else 1 end')->orderByDesc('products.id');
    }

    /**
     * The price paid today: the offer price while the offer runs (dates inclusive), else the regular price.
     * Variant products mirror their variants' prices on these columns.
     */
    private function effectivePrice(): string
    {
        return 'case when products.offer_price is not null'
            .' and (products.offer_start_date is null or products.offer_start_date <= ?)'
            .' and (products.offer_end_date is null or products.offer_end_date >= ?)'
            .' then products.offer_price else products.price end';
    }

    /**
     * @return array<int, string>
     */
    private function effectivePriceBindings(): array
    {
        // The end of today for the start date and the bare date for the end date: correct for DATE columns
        // and for dates stored as "Y-m-d H:i:s" text (SQLite).
        return [today()->format('Y-m-d').' 23:59:59', today()->format('Y-m-d')];
    }

    private function usesFullText(SearchTerm $term): bool
    {
        return $term->length() >= SearchTerm::FULLTEXT_MIN_LENGTH
            && Product::query()->getConnection()->getDriverName() === 'mysql';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSuggestions(SearchTerm $term, ?Category $category): array
    {
        $query = $this->query($term, $category, null);
        $this->orderByRelevance($query, $term);

        $products = $query->limit(self::SUGGEST_PRODUCTS)->get()->map(fn (Product $product) => [
            'name' => $product->name,
            'name_parts' => $this->highlight($product->name, $term),
            'url' => route('product.show', $product->slug),
            'image' => $product->thumbnail_url,
            'price' => $product->current_price_label,
            'old_price' => $product->discount_percent > 0 ? $product->price_label : null,
        ])->all();

        return [
            'term' => $term->value,
            'products' => $products,
            'categories' => $this->matchingCategories($term, $category),
            'brands' => $this->matchingBrands($term),
            'popular' => $products === [] ? $this->popularCategories() : [],
            'more_url' => route('shop.index', array_filter(['search' => $term->value, 'category' => $category?->slug])),
        ];
    }

    /**
     * Category levels whose name contains the term, names starting with it first. Read from the cached menu
     * tree (active categories shown in the menu), so no query runs.
     *
     * @return array<int, array<string, mixed>>
     */
    private function matchingCategories(SearchTerm $term, ?Category $within): array
    {
        $needle = $term->normalized();
        $found = [];

        foreach ($this->categoryMenu->tree() as $top) {
            if ($within && $top['id'] !== $within->id) {
                continue;
            }

            $found[] = ['name' => $top['name'], 'context' => null, 'url' => route('category.show', $top['slug'])];

            foreach ($top['subs'] as $sub) {
                $found[] = ['name' => $sub['name'], 'context' => $top['name'], 'url' => route('category.sub.show', [$top['slug'], $sub['slug']])];

                foreach ($sub['children'] as $child) {
                    $found[] = [
                        'name' => $child['name'],
                        'context' => $top['name'].' › '.$sub['name'],
                        'url' => route('category.child.show', [$top['slug'], $sub['slug'], $child['slug']]),
                    ];
                }
            }
        }

        return collect($found)
            ->filter(fn (array $c) => Str::contains(Str::lower($c['name']), $needle))
            ->sortBy(fn (array $c) => Str::startsWith(Str::lower($c['name']), $needle) ? 0 : 1)
            ->take(self::SUGGEST_CATEGORIES)
            ->map(fn (array $c) => $c + ['name_parts' => $this->highlight($c['name'], $term)])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function matchingBrands(SearchTerm $term): array
    {
        return Brand::query()
            ->active()
            ->whereRaw('brands.name '.self::LIKE, [$term->contains()])
            ->orderByRaw('case when brands.name '.self::LIKE.' then 0 else 1 end', [$term->startsWith()])
            ->orderBy('name')
            ->limit(self::SUGGEST_BRANDS)
            ->get(['name', 'slug'])
            ->map(fn (Brand $brand) => [
                'name' => $brand->name,
                'name_parts' => $this->highlight($brand->name, $term),
                'url' => route('shop.index', ['brand' => $brand->slug]),
            ])
            ->all();
    }

    /**
     * Shown when nothing matches. There is no popularity data yet, so these are the first top-level categories
     * of the menu, in menu order.
     *
     * @return array<int, array<string, string>>
     */
    public function popularCategories(): array
    {
        return $this->categoryMenu->tree()
            ->take(self::POPULAR_CATEGORIES)
            ->map(fn (array $c) => ['name' => $c['name'], 'url' => route('category.show', $c['slug'])])
            ->values()
            ->all();
    }

    /**
     * Splits the text into parts, flagging every case-insensitive occurrence of the term's words.
     *
     * @return array<int, array{text: string, match: bool}>
     */
    private function highlight(string $text, SearchTerm $term): array
    {
        $words = collect(explode(' ', $term->value))
            ->filter(fn (string $w) => $w !== '')
            ->unique()
            ->sortByDesc(fn (string $w) => Str::length($w))
            ->map(fn (string $w) => preg_quote($w, '/'))
            ->implode('|');

        if ($words === '') {
            return [['text' => $text, 'match' => false]];
        }

        $pieces = preg_split('/('.$words.')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [$text];

        return array_map(fn (string $piece) => ['text' => $piece, 'match' => (bool) preg_match('/^(?:'.$words.')$/iu', $piece)], $pieces);
    }
}
