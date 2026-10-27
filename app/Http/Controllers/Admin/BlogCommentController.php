<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\BlogCommentDataTable;
use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TableRequest;
use App\Models\BlogComment;
use App\Services\BlogCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BlogCommentController extends Controller
{
    public function __construct(private readonly BlogCommentService $comments) {}

    public function index(): View
    {
        return view('admin.blog-comments.index');
    }

    public function data(TableRequest $request, BlogCommentDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function approve(BlogComment $blogComment): JsonResponse
    {
        $this->comments->setStatus($blogComment, CommentStatus::Approved);

        return response()->json(['message' => 'Comment approved.']);
    }

    public function spam(BlogComment $blogComment): JsonResponse
    {
        $this->comments->setStatus($blogComment, CommentStatus::Spam);

        return response()->json(['message' => 'Comment marked as spam.']);
    }

    public function destroy(BlogComment $blogComment): JsonResponse
    {
        $this->comments->delete($blogComment);

        return response()->json(['message' => 'Comment deleted.']);
    }
}
