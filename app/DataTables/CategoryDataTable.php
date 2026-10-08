<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin categories table (no page filters, only the table's own search).
 * Column keys must match the "columns" in resources/views/admin/categories/index.blade.php.
 */
class CategoryDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('visual', fn (Category $category) => view('admin.categories.partials.visual', ['category' => $category])->render())
            ->addColumn('is_active', fn (Category $category) => view('admin.partials.table.status', [
                'model' => $category,
                'url' => route('admin.categories.status', $category),
                'label' => $category->name,
            ])->render())
            ->addColumn('action', fn (Category $category) => view('admin.partials.table.actions', [
                'editUrl' => route('admin.categories.edit', $category),
                'deleteUrl' => route('admin.categories.destroy', $category),
                'title' => $category->name,
                'noun' => 'category',
            ])->render())
            ->orderColumn('sub_categories_count', fn ($query, $dir) => $query->orderBy('sub_categories_count', $dir))
            // A filter callback replaces yajra's automatic per-column search.
            ->filter(fn ($query) => $query->when(
                filled($request->input('search.value')),
                fn ($q) => $q->where('name', 'like', '%'.$request->input('search.value').'%'),
            ))
            ->rawColumns(['visual', 'is_active', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<Category>
     */
    private function query(TableRequest $request): Builder
    {
        return Category::query()
            ->withCount('subCategories')
            ->when(! $request->has('order'), fn (Builder $query) => $query->ordered());
    }
}
