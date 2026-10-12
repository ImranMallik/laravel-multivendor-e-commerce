<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use App\Services\CategoryBrowseService;
use Illuminate\View\View;

/**
 * Storefront listings: all visible products, or those filed under one category level.
 * TODO: sorting, price / brand filters and the sidebar of the template's shop page.
 */
class ShopController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function index(): View
    {
        return view('frontend.shop.index', ['products' => $this->products->paginate()]);
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
