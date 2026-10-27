{{-- Commenter name, email, and "reply" when the comment answers another one. Expects: $comment. --}}
<div class="font-weight-bold">{{ $comment->name }}</div>
<div class="text-muted small">{{ $comment->email }}</div>
@if ($comment->parent_id)
    <span class="badge badge-light">reply</span>
@endif
