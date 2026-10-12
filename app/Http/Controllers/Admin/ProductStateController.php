<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductFlag;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductApprovalService;
use App\Services\ProductService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Small AJAX actions on a product: status / flag switches, approve / reject, removing a gallery image.
 */
class ProductStateController extends Controller
{
    public function status(Product $product, ProductService $products): JsonResponse
    {
        $product = $products->toggleStatus($product, auth('admin')->user());
        $active = $product->status === ProductStatus::Active;

        return response()->json([
            'message' => $active ? 'Product activated.' : 'Product deactivated.',
            'is_active' => $active,
        ]);
    }

    public function flag(Product $product, string $flag, ProductService $products): JsonResponse
    {
        $flag = ProductFlag::from($flag);
        $product = $products->toggleFlag($product, $flag);
        $on = (bool) $product->{$flag->value};

        return response()->json([
            'message' => $flag->label().($on ? ' turned on.' : ' turned off.'),
            $flag->value => $on,
        ]);
    }

    public function approve(Product $product, ProductApprovalService $approval): JsonResponse|RedirectResponse
    {
        return $this->respond(fn () => $approval->approve($product, auth('admin')->user()), 'Product approved.');
    }

    public function reject(ReasonRequest $request, Product $product, ProductApprovalService $approval): JsonResponse|RedirectResponse
    {
        return $this->respond(fn () => $approval->reject($product, $request->validated('reason'), auth('admin')->user()), 'Product rejected.');
    }

    public function destroyImage(Product $product, ProductImage $image, ProductService $products): JsonResponse
    {
        $products->deleteImage($image, auth('admin')->user());

        return response()->json(['message' => 'Image removed.']);
    }

    private function respond(callable $change, string $message): JsonResponse|RedirectResponse
    {
        try {
            $product = $change();
        } catch (DomainException $e) {
            return request()->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return request()->expectsJson()
            ? response()->json(['message' => $message, 'approval_status' => $product->approval_status->value])
            : back()->with('success', $message);
    }
}
