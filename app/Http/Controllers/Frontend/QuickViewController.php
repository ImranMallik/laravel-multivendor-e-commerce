<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;

class QuickViewController extends Controller
{
    /**
     * The quick view modal body of one product, rendered HTML inside JSON. Only products visible on the
     * storefront: anything else (inactive, unapproved, suspended shop, inactive category, missing) is a 404.
     */
    public function __invoke(string $slug, ProductRepository $products): JsonResponse
    {
        $product = $products->findVisibleBySlug($slug);

        return response()->json([
            'name' => $product->name,
            'html' => view('frontend.partials.quick-view-body', ['product' => $product])->render(),
        ]);
    }
}
