<?php

namespace App\DataTables;

use App\Enums\ProductApprovalStatus;
use App\Http\Requests\Admin\ProductTableRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin products table (and the Pending Products page).
 * Column keys must match public/admin-assets/js/products-table.js.
 */
class ProductDataTable
{
    public function json(ProductTableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('thumbnail', fn (Product $product) => view('admin.products.partials.thumbnail', ['product' => $product])->render())
            ->editColumn('name', fn (Product $product) => view('admin.products.partials.name', ['product' => $product])->render())
            ->addColumn('vendor', fn (Product $product) => e($product->vendor?->shop_name ?? 'In-house'))
            ->addColumn('category', fn (Product $product) => e($product->category?->name ?? '—'))
            ->editColumn('price', fn (Product $product) => view('admin.products.partials.price', ['product' => $product])->render())
            ->editColumn('stock_qty', fn (Product $product) => $product->total_stock > 0
                ? (string) $product->total_stock
                : Blade::render('<x-admin.badge color="danger">Out of stock</x-admin.badge>'))
            ->editColumn('status', fn (Product $product) => view('admin.partials.table.toggle', [
                'checked' => $product->status->value === 'active',
                'url' => route('admin.products.status', $product),
                'field' => 'is_active',
                'label' => 'Active: '.$product->name,
            ])->render())
            ->editColumn('approval_status', fn (Product $product) => Blade::render('<x-admin.badge :status="$status" />', ['status' => $product->approval_status]))
            ->addColumn('flags', fn (Product $product) => view('admin.products.partials.flags', ['product' => $product])->render())
            ->addColumn('action', fn (Product $product) => view('admin.products.partials.actions', ['product' => $product])->render())
            ->orderColumn('vendor', fn ($query, $dir) => $query->orderBy(
                Vendor::select('shop_name')->whereColumn('vendors.id', 'products.vendor_id'), $dir
            ))
            ->orderColumn('category', fn ($query, $dir) => $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'products.category_id'), $dir
            ))
            // A filter callback replaces yajra's automatic search: name, SKU and vendor shop name.
            ->filter(fn ($query) => $query->when(filled($request->input('search.value')), function ($q) use ($request) {
                $term = '%'.$request->input('search.value').'%';

                $q->where(fn ($w) => $w
                    ->where('products.name', 'like', $term)
                    ->orWhere('products.sku', 'like', $term)
                    ->orWhereHas('vendor', fn ($v) => $v->where('shop_name', 'like', $term)));
            }))
            ->rawColumns(['thumbnail', 'name', 'price', 'stock_qty', 'status', 'approval_status', 'flags', 'action'])
            ->with('pending', Product::where('approval_status', ProductApprovalStatus::Pending->value)->count())
            ->toJson();
    }

    /**
     * @return Builder<Product>
     */
    private function query(ProductTableRequest $request): Builder
    {
        $vendor = $request->validated('vendor');

        return Product::query()
            ->with(['vendor:id,shop_name', 'category:id,name', 'activeVariants'])
            ->select('products.*')
            ->when($vendor === 'inhouse', fn (Builder $q) => $q->whereNull('products.vendor_id'))
            ->when($vendor !== null && $vendor !== 'inhouse', fn (Builder $q) => $q->where('products.vendor_id', (int) $vendor))
            ->when($request->validated('category_id'), fn (Builder $q, $id) => $q->where('products.category_id', $id))
            ->when($request->validated('approval_status'), fn (Builder $q, $status) => $q->where('products.approval_status', $status))
            ->when(! $request->has('order'), fn (Builder $q) => $q->latest('products.id'));
    }
}
