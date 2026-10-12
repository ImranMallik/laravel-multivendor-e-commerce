<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin attributes table (no page filters, only the table's own search).
 * Column keys must match the "columns" in resources/views/admin/attributes/index.blade.php.
 */
class AttributeDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->editColumn('type', fn (Attribute $attribute) => e($attribute->type->label()))
            ->addColumn('values_list', fn (Attribute $attribute) => view('admin.attributes.partials.values', ['attribute' => $attribute])->render())
            ->editColumn('is_active', fn (Attribute $attribute) => view('admin.partials.table.status', [
                'model' => $attribute,
                'url' => route('admin.attributes.status', $attribute),
                'label' => $attribute->name,
            ])->render())
            ->addColumn('action', fn (Attribute $attribute) => view('admin.partials.table.actions', [
                'editUrl' => route('admin.attributes.edit', $attribute),
                'deleteUrl' => route('admin.attributes.destroy', $attribute),
                'title' => $attribute->name,
                'noun' => 'attribute',
            ])->render())
            ->orderColumn('values_list', fn ($query, $dir) => $query->orderBy('values_count', $dir))
            ->filter(fn ($query) => $query->when(
                filled($request->input('search.value')),
                fn ($q) => $q->where('name', 'like', '%'.$request->input('search.value').'%'),
            ))
            ->rawColumns(['values_list', 'is_active', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<Attribute>
     */
    private function query(TableRequest $request): Builder
    {
        return Attribute::query()
            ->select('attributes.*')
            ->withCount('values')
            ->with('values')
            ->when(! $request->has('order'), fn (Builder $q) => $q->ordered());
    }
}
