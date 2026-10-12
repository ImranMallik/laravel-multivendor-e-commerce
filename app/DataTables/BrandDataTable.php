<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin brands table (no page filters, only the table's own search).
 * Column keys must match the "columns" in resources/views/admin/brands/index.blade.php.
 */
class BrandDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('logo', fn (Brand $brand) => view('admin.brands.partials.logo', ['brand' => $brand])->render())
            ->addColumn('is_featured', fn (Brand $brand) => view('admin.partials.table.toggle', [
                'checked' => $brand->is_featured,
                'url' => route('admin.brands.featured', $brand),
                'field' => 'is_featured',
                'label' => 'Featured: '.$brand->name,
            ])->render())
            ->addColumn('is_active', fn (Brand $brand) => view('admin.partials.table.toggle', [
                'checked' => $brand->is_active,
                'url' => route('admin.brands.status', $brand),
                'field' => 'is_active',
                'label' => 'Active: '.$brand->name,
            ])->render())
            ->editColumn('created_at', fn (Brand $brand) => $brand->created_at->format('d M Y'))
            ->addColumn('action', fn (Brand $brand) => view('admin.partials.table.actions', [
                'editUrl' => route('admin.brands.edit', $brand),
                'deleteUrl' => route('admin.brands.destroy', $brand),
                'title' => $brand->name,
                'noun' => 'brand',
            ])->render())
            // A filter callback replaces yajra's automatic per-column search; only the name is searched.
            ->filter(fn ($query) => $query->when(
                filled($request->input('search.value')),
                fn ($q) => $q->where('name', 'like', '%'.$request->input('search.value').'%'),
            ))
            ->rawColumns(['logo', 'is_featured', 'is_active', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<Brand>
     */
    private function query(TableRequest $request): Builder
    {
        return Brand::query()
            ->when(! $request->has('order'), fn (Builder $query) => $query->ordered());
    }
}
