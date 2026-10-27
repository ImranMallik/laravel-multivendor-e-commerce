<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin blog posts table (no page filters, only the table search on title).
 * Column keys must match the "columns" in resources/views/admin/blogs/index.blade.php.
 */
class BlogDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query())
            ->addIndexColumn()
            ->addColumn('image', fn (Blog $blog) => view('admin.blogs.partials.image', ['blog' => $blog])->render())
            ->addColumn('category', fn (Blog $blog) => $blog->category?->name ?? '—')
            ->addColumn('status', fn (Blog $blog) => view('admin.blogs.partials.status', ['blog' => $blog])->render())
            ->addColumn('show_on_home', fn (Blog $blog) => view('admin.partials.table.toggle', [
                'checked' => $blog->show_on_home,
                'url' => route('admin.blogs.show-on-home', $blog),
                'field' => 'show_on_home',
                'label' => 'Show on home: '.$blog->title,
            ])->render())
            ->editColumn('published_at', fn (Blog $blog) => $blog->published_at
                ? $blog->published_at->copy()->setTimezone(config('marketplace.timezone'))->format('d M Y, H:i')
                : '—')
            ->addColumn('action', fn (Blog $blog) => view('admin.blogs.partials.actions', ['blog' => $blog])->render())
            ->filter(fn ($query) => $query->when(
                filled($request->input('search.value')),
                fn ($q) => $q->where('blogs.title', 'like', '%'.$request->input('search.value').'%'),
            ))
            ->rawColumns(['image', 'status', 'show_on_home', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<Blog>
     */
    private function query(): Builder
    {
        return Blog::query()->with('category:id,name')->select('blogs.*');
    }
}
