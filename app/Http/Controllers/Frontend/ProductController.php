<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Product page by slug; a product that is not visible on the storefront is a 404.
     */
    public function show(string $slug, ProductRepository $products): View
    {
        $product = $products->findVisibleBySlug($slug);
        $specifications = $product->specifications;

        return view('frontend.product.show', [
            'product' => $product,
            'breadcrumb' => $this->breadcrumb($product),
            // The template shows the "Information" tab in two columns.
            'specificationColumns' => $specifications->isEmpty() ? [] : $specifications->chunk((int) ceil($specifications->count() / 2))->all(),
            'related' => $products->related($product),
        ]);
    }

    /**
     * @return array<string, string|null> label => url
     */
    private function breadcrumb(Product $product): array
    {
        $trail = [$product->category->name => route('category.show', $product->category->slug)];

        if ($product->subCategory) {
            $trail[$product->subCategory->name] = route('category.sub.show', [$product->category->slug, $product->subCategory->slug]);
        }

        if ($product->subCategory && $product->childCategory) {
            $trail[$product->childCategory->name] = route('category.child.show', [$product->category->slug, $product->subCategory->slug, $product->childCategory->slug]);
        }

        return $trail + [$product->name => null];
    }
}
