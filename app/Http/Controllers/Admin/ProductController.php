<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ProductDataTable;
use App\Enums\ProductApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\ProductTableRequest;
use App\Models\Product;
use App\Services\ProductService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', ['pendingOnly' => false]);
    }

    public function pending(): View
    {
        return view('admin.products.index', ['pendingOnly' => true]);
    }

    public function data(ProductTableRequest $request, ProductDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(ProductRequest $request, ProductService $products): RedirectResponse
    {
        $product = $products->create($request->productData(), auth('admin')->user());

        return redirect()->route('admin.products.show', $product)->with('success', 'Product created.');
    }

    public function show(Product $product): View
    {
        return view('admin.products.show', [
            'product' => $product->load([
                'vendor.user', 'category', 'subCategory', 'childCategory', 'brand', 'images', 'specifications', 'approvalLogs.admin', 'createdBy',
            ]),
            'canApprove' => $product->approval_status->canTransitionTo(ProductApprovalStatus::Approved),
            'canReject' => $product->approval_status->canTransitionTo(ProductApprovalStatus::Rejected),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', ['product' => $product->load(['images', 'specifications'])]);
    }

    public function update(ProductRequest $request, Product $product, ProductService $products): RedirectResponse
    {
        try {
            $products->update($product, $request->productData(), auth('admin')->user());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Request $request, Product $product, ProductService $products): JsonResponse|RedirectResponse
    {
        $products->delete($product);

        return $request->expectsJson()
            ? response()->json(['message' => 'Product deleted.'])
            : redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
