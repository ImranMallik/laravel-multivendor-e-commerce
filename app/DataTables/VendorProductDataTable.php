<?php

namespace App\DataTables;

use App\Http\Requests\Vendor\ProductTableRequest;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the vendor's own products (vendor panel). Always scoped to the signed-in
 * user's shop. Column keys must match public/frontend-assets/js/vendor-products-table.js.
 */
class VendorProductDataTable
{
    public function json(ProductTableRequest $request, Vendor $vendor): JsonResponse
    {
        return DataTables::eloquent($this->query($request, $vendor))
            ->addIndexColumn()
            ->addColumn('thumbnail', fn (Product $product) => view('vendor.products.partials.thumbnail', ['product' => $product])->render())
            ->editColumn('name', fn (Product $product) => view('vendor.products.partials.name', ['product' => $product])->render())
            ->addColumn('category', fn (Product $product) => e($product->category?->name ?? '—'))
            ->editColumn('price', fn (Product $product) => view('vendor.products.partials.price', ['product' => $product])->render())
            ->editColumn('stock_qty', fn (Product $product) => view('vendor.products.partials.stock', ['product' => $product])->render())
            ->editColumn('status', fn (Product $product) => view('vendor.products.partials.status', ['product' => $product])->render())
            ->editColumn('approval_status', fn (Product $product) => view('vendor.products.partials.approval', ['product' => $product])->render())
            ->addColumn('action', fn (Product $product) => view('vendor.products.partials.actions', ['product' => $product])->render())
            ->filter(fn ($query) => $query->when(filled($request->input('search.value')), function ($q) use ($request) {
                $term = '%'.$request->input('search.value').'%';

                $q->where(fn ($w) => $w->where('products.name', 'like', $term)->orWhere('products.sku', 'like', $term));
            }))
            ->rawColumns(['thumbnail', 'name', 'price', 'stock_qty', 'status', 'approval_status', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<Product>
     */
    private function query(ProductTableRequest $request, Vendor $vendor): Builder
    {
        return Product::query()
            ->where('products.vendor_id', $vendor->id)
            ->with(['category:id,name', 'activeVariants'])
            ->select('products.*')
            ->when(! $request->has('order'), fn (Builder $q) => $q->latest('products.id'));
    }
}
