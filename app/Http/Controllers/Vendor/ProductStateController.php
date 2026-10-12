<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor AJAX actions on their own product: the Active / Inactive switch and removing a gallery image.
 */
class ProductStateController extends Controller
{
    public function status(Request $request, Product $product, ProductService $products): JsonResponse
    {
        $this->authorize('toggleStatus', $product);

        $product = $products->toggleStatus($product, $request->user());
        $active = $product->status === ProductStatus::Active;

        return response()->json([
            'message' => $active ? 'Product activated.' : 'Product deactivated.',
            'is_active' => $active,
        ]);
    }

    public function destroyImage(Request $request, Product $product, ProductImage $image, ProductService $products): JsonResponse
    {
        $this->authorize('update', $product);

        $products->deleteImage($image, $request->user());

        return response()->json([
            'message' => $product->fresh()->isApproved() ? 'Image removed.' : 'Image removed. The product is waiting for review again.',
        ]);
    }
}
