<?php

namespace App\Services;

use App\Enums\CommentStatus;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\User;

/**
 * Storefront comments (always start Pending) and their moderation in the admin panel.
 */
class BlogCommentService
{
    /**
     * @param  array{comment: string, name?: string|null, email?: string|null, parent_id?: int|null}  $data
     */
    public function submit(Blog $blog, array $data, ?User $user, ?string $ip): BlogComment
    {
        return $blog->comments()->create([
            'parent_id' => $data['parent_id'] ?? null,
            'user_id' => $user?->id,
            // Signed-in users comment under their account name and email.
            'name' => $user?->name ?? $data['name'],
            'email' => $user?->email ?? $data['email'],
            'comment' => trim($data['comment']),
            'status' => CommentStatus::Pending,
            'ip_address' => $ip,
        ]);
    }

    public function setStatus(BlogComment $comment, CommentStatus $status): BlogComment
    {
        $comment->update(['status' => $status]);

        return $comment;
    }

    /**
     * Deletes the comment and (by cascade) its replies.
     */
    public function delete(BlogComment $comment): void
    {
        $comment->delete();
    }
}
