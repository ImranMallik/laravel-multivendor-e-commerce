<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin blog categories table (no page filters, only the table search).
 * Column keys must match the "columns" in resources/views/admin/blog-categories/index.blade.php.
 */
class BlogCategoryDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('is_active', fn (BlogCategory $category) => view('admin.partials.table.status', [
                'model' => $category,
                'url' => route('admin.blog-categories.status', $category),
                'label' => $category->name,
            ])->render())
            ->editColumn('created_at', fn (BlogCategory $category) => $category->created_at->format('d M Y'))
            ->addColumn('action', fn (BlogCategory $category) => view('admin.partials.table.actions', [
                'editUrl' => route('admin.blog-categories.edit', $category),
                'deleteUrl' => route('admin.blog-categories.destroy', $category),
                'title' => $category->name,
                'noun' => 'category',
            ])->render())
            ->filter(fn ($query) => $query->when(
                filled($request->input('search.value')),
                fn ($q) => $q->where('name', 'like', '%'.$request->input('search.value').'%'),
            ))
            ->rawColumns(['is_active', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<BlogCategory>
     */
    private function query(TableRequest $request): Builder
    {
        return BlogCategory::query()
            ->withCount('blogs')
            ->when(! $request->has('order'), fn (Builder $query) => $query->ordered());
    }
}
