{{-- Pending / Approved / Spam badge. Expects: $comment. --}}
<x-admin.badge :status="$comment->status" />
