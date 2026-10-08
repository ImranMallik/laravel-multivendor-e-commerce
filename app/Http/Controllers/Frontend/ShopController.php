<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\CategoryBrowseService;
use App\Services\CategoryMenuService;
use Illuminate\View\View;

/**
 * Placeholder storefront browsing pages. TODO: list products filtered by the chosen
 * category / sub category / child category once products exist.
 */
class ShopController extends Controller
{
    public function index(CategoryMenuService $menu): View
    {
        return view('frontend.shop.index', ['categories' => $menu->tree()]);
    }

    public function category(CategoryBrowseService $browse, string $categorySlug): View
    {
        return view('frontend.shop.category', $browse->category($categorySlug));
    }

    public function subCategory(CategoryBrowseService $browse, string $categorySlug, string $subSlug): View
    {
        return view('frontend.shop.category', $browse->subCategory($categorySlug, $subSlug));
    }

    public function childCategory(CategoryBrowseService $browse, string $categorySlug, string $subSlug, string $childSlug): View
    {
        return view('frontend.shop.category', $browse->childCategory($categorySlug, $subSlug, $childSlug));
    }
}
