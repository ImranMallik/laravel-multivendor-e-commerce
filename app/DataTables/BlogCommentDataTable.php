<?php

namespace App\DataTables;

use App\Enums\CommentStatus;
use App\Http\Requests\Admin\TableRequest;
use App\Models\BlogComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin blog comments table (pending first, then newest; no page filters).
 * Column keys must match the "columns" in resources/views/admin/blog-comments/index.blade.php.
 */
class BlogCommentDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        $search = $request->input('search.value');

        return DataTables::eloquent($this->query($request))
            ->addIndexColumn()
            ->addColumn('author', fn (BlogComment $comment) => view('admin.blog-comments.partials.author', ['comment' => $comment])->render())
            ->editColumn('comment', fn (BlogComment $comment) => Str::limit($comment->comment, 160))
            ->addColumn('post', fn (BlogComment $comment) => $comment->blog?->title ?? '—')
            ->editColumn('status', fn (BlogComment $comment) => view('admin.blog-comments.partials.status', ['comment' => $comment])->render())
            ->editColumn('created_at', fn (BlogComment $comment) => $comment->created_at->format('d M Y, H:i'))
            ->addColumn('action', fn (BlogComment $comment) => view('admin.blog-comments.partials.actions', ['comment' => $comment])->render())
            ->filter(fn ($query) => $query->when(filled($search), fn ($q) => $q->where(fn ($w) => $w
                ->where('blog_comments.name', 'like', '%'.$search.'%')
                ->orWhere('blog_comments.email', 'like', '%'.$search.'%')
                ->orWhere('blog_comments.comment', 'like', '%'.$search.'%'))))
            ->rawColumns(['author', 'status', 'action'])
            ->toJson();
    }

    /**
     * @return Builder<BlogComment>
     */
    private function query(TableRequest $request): Builder
    {
        // Pending comments always come first; the column the admin sorts by orders within each group.
        return BlogComment::query()
            ->with('blog:id,title,slug')
            ->select('blog_comments.*')
            ->orderByRaw('CASE WHEN blog_comments.status = ? THEN 0 ELSE 1 END', [CommentStatus::Pending->value])
            ->when(! $request->has('order'), fn (Builder $query) => $query->latest('blog_comments.id'));
    }
}
