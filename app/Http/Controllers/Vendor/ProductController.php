<?php

namespace App\Http\Controllers\Vendor;

use App\DataTables\VendorProductDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ProductRequest;
use App\Http\Requests\Vendor\ProductTableRequest;
use App\Models\Product;
use App\Services\ProductService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The seller's own products. The shop always comes from the signed-in user; every action on an
 * existing product goes through ProductPolicy (another shop's product is a 403).
 */
class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('create', Product::class);

        return view('vendor.products.index');
    }

    public function data(ProductTableRequest $request, VendorProductDataTable $table): JsonResponse
    {
        return $table->json($request, $request->user()->vendor);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('vendor.products.create', ['reviewRequired' => (bool) config('marketplace.vendor_product_approval', true)]);
    }

    public function store(ProductRequest $request, ProductService $products): RedirectResponse
    {
        $product = $products->create($request->productData(), $request->user());

        return redirect()->route('vendor.products.index')->with('success', $product->isApproved()
            ? 'Product created.'
            : 'Product submitted. It will appear in the store once an admin approves it.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('vendor.products.edit', [
            'product' => $product->load(['images', 'specifications']),
            'reviewRequired' => (bool) config('marketplace.vendor_product_approval', true),
        ]);
    }

    public function update(ProductRequest $request, Product $product, ProductService $products): RedirectResponse
    {
        try {
            $product = $products->update($product, $request->productData(), $request->user());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.products.index')->with('success', $product->isApproved()
            ? 'Product updated.'
            : 'Product saved and sent for review.');
    }

    public function destroy(Request $request, Product $product, ProductService $products): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $product);

        $products->delete($product);

        return $request->expectsJson()
            ? response()->json(['message' => 'Product deleted.'])
            : redirect()->route('vendor.products.index')->with('success', 'Product deleted.');
    }
}
