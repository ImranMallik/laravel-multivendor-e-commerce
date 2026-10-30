<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ProductSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ShopSearchRequest;
use App\Repositories\ProductRepository;
use App\Services\CategoryBrowseService;
use App\Services\ProductSearchService;
use App\Services\SearchLogger;
use Illuminate\View\View;

/**
 * Storefront listings: all visible products (with search, sorting and the category / brand URL filters), or
 * those filed under one category level.
 * TODO: the template's shop sidebar (category accordion, price slider, brand checkboxes) and the list view.
 */
class ShopController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function index(ShopSearchRequest $request, ProductSearchService $search, SearchLogger $logger): View
    {
        $term = $request->term();
        $searching = ! $term->isEmpty();
        $category = $search->findCategory($request->categorySlug());
        $brand = $search->findBrand($request->brandSlug());
        $sort = $request->sort($searching);
        $products = $search->paginate($term, $sort, $category, $brand);

        // One log row per submitted search: later pages and re-sorting of the same results are not counted.
        if ($searching && $products->currentPage() === 1 && ! $request->has('sort')) {
            $logger->record($term, $products->total(), $request->user('web')?->id);
        }

        return view('frontend.shop.index', [
            'products' => $products,
            'term' => $term->value,
            'searching' => $searching,
            'category' => $category,
            'brand' => $brand,
            'sort' => $sort,
            'sortOptions' => ProductSort::options($searching),
            // The sort form keeps the other filters as hidden fields.
            'keep' => array_filter(['search' => $term->value, 'category' => $category?->slug, 'brand' => $brand?->slug]),
            'popularCategories' => $searching && $products->isEmpty() ? $search->popularCategories() : [],
        ]);
    }

    public function category(CategoryBrowseService $browse, string $categorySlug): View
    {
        return $this->listing($browse->category($categorySlug));
    }

    public function subCategory(CategoryBrowseService $browse, string $categorySlug, string $subSlug): View
    {
        return $this->listing($browse->subCategory($categorySlug, $subSlug));
    }

    public function childCategory(CategoryBrowseService $browse, string $categorySlug, string $subSlug, string $childSlug): View
    {
        return $this->listing($browse->childCategory($categorySlug, $subSlug, $childSlug));
    }

    /**
     * @param  array{title: string, trail: array<string, string|null>, links: array<string, string>, filter: array<string, int>}  $page
     */
    private function listing(array $page): View
    {
        return view('frontend.shop.category', $page + ['products' => $this->products->paginate($page['filter'])]);
    }
}
