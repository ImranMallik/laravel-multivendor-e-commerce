{{-- Approve / Spam (AJAX PATCH, blog-comments.js), View post, Delete (crud-table.js). Expects: $comment. --}}
@if ($comment->status !== \App\Enums\CommentStatus::Approved)
    <x-admin.button variant="success" icon="fas fa-check" size="sm" tooltip="Approve"
                    data-comment-action data-url="{{ route('admin.blog-comments.approve', $comment) }}" />
@endif
@if ($comment->status !== \App\Enums\CommentStatus::Spam)
    <x-admin.button variant="warning" icon="fas fa-ban" size="sm" tooltip="Mark as spam"
                    data-comment-action data-url="{{ route('admin.blog-comments.spam', $comment) }}" />
@endif
@if ($comment->blog && $comment->blog->isPublished())
    <x-admin.button :href="route('frontend.blog.show', $comment->blog->slug).'#comments'" variant="info" icon="fas fa-external-link-alt"
                    size="sm" tooltip="View post" target="_blank" rel="noopener" />
@endif
<x-admin.button variant="danger" icon="fas fa-trash" size="sm" tooltip="Delete"
                data-delete-row
                data-url="{{ route('admin.blog-comments.destroy', $comment) }}"
                data-title="{{ \Illuminate\Support\Str::limit($comment->comment, 40) }}"
                data-noun="comment" />
