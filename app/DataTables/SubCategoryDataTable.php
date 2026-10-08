<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin sub categories table.
 * Column keys must match the "columns" in resources/views/admin/sub-categories/index.blade.php.
 */
class SubCategoryDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('category', fn (SubCategory $subCategory) => $subCategory->category?->name)
            ->addColumn('is_active', fn (SubCategory $subCategory) => view('admin.partials.table.status', [
                'model' => $subCategory,
                'url' => route('admin.sub-categories.status', $subCategory),
                'label' => $subCategory->name,
            ])->render())
            ->addColumn('action', fn (SubCategory $subCategory) => view('admin.partials.table.actions', [
                'editUrl' => route('admin.sub-categories.edit', $subCategory),
                'deleteUrl' => route('admin.sub-categories.destroy', $subCategory),
                'title' => $subCategory->name,
                'noun' => 'sub category',
            ])->render())
            ->orderColumn('category', fn ($query, $dir) => $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'sub_categories.category_id'), $dir
            ))
            ->orderColumn('child_categories_count', fn ($query, $dir) => $query->orderBy('child_categories_count', $dir))
            // Search covers the sub category name and its parent category name.
            ->filter(function ($query) use ($request) {
                $term = $request->input('search.value');

                $query->when(filled($term), fn ($q) => $q->where(function ($inner) use ($term) {
                    $inner->where('sub_categories.name', 'like', "%{$term}%")
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$term}%"));
                }));
            })
            ->rawColumns(['is_active', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<SubCategory>
     */
    private function query(TableRequest $request): Builder
    {
        return SubCategory::query()
            ->select('sub_categories.*')
            ->with('category:id,name')
            // withCount must come after select(), which would otherwise replace the count column.
            ->withCount('childCategories')
            ->when(! $request->has('order'), fn (Builder $query) => $query->ordered());
    }
}
