<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin child categories table.
 * Column keys must match the "columns" in resources/views/admin/child-categories/index.blade.php.
 */
class ChildCategoryDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('category', fn (ChildCategory $child) => $child->category?->name)
            ->addColumn('sub_category', fn (ChildCategory $child) => $child->subCategory?->name)
            ->addColumn('is_active', fn (ChildCategory $child) => view('admin.partials.table.status', [
                'model' => $child,
                'url' => route('admin.child-categories.status', $child),
                'label' => $child->name,
            ])->render())
            ->addColumn('action', fn (ChildCategory $child) => view('admin.partials.table.actions', [
                'editUrl' => route('admin.child-categories.edit', $child),
                'deleteUrl' => route('admin.child-categories.destroy', $child),
                'title' => $child->name,
                'noun' => 'child category',
            ])->render())
            ->orderColumn('category', fn ($query, $dir) => $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'child_categories.category_id'), $dir
            ))
            ->orderColumn('sub_category', fn ($query, $dir) => $query->orderBy(
                SubCategory::select('name')->whereColumn('sub_categories.id', 'child_categories.sub_category_id'), $dir
            ))
            // Search covers the child name and both parent names.
            ->filter(function ($query) use ($request) {
                $term = $request->input('search.value');

                $query->when(filled($term), fn ($q) => $q->where(function ($inner) use ($term) {
                    $inner->where('child_categories.name', 'like', "%{$term}%")
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('subCategory', fn ($sub) => $sub->where('name', 'like', "%{$term}%"));
                }));
            })
            ->rawColumns(['is_active', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<ChildCategory>
     */
    private function query(TableRequest $request): Builder
    {
        return ChildCategory::query()
            ->with(['category:id,name', 'subCategory:id,name'])
            ->select('child_categories.*')
            ->when(! $request->has('order'), fn (Builder $query) => $query->ordered());
    }
}
