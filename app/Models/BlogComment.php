<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A comment on a blog post, or a reply to one (one level: parent_id points at a top-level comment).
 */
class BlogComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'parent_id',
        'user_id',
        'name',
        'email',
        'comment',
        'status',
        'ip_address',
    ];

    protected $casts = [
        'status' => CommentStatus::class,
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class)->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('blog_comments.status', CommentStatus::Approved->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('blog_comments.status', CommentStatus::Pending->value);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('blog_comments.parent_id');
    }

    /**
     * The commenter's avatar when signed in with one, else the template's placeholder face.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->user?->hasAvatar()
            ? $this->user->avatar_url
            : asset('frontend-assets/images/client_img_1.jpg'));
    }
}
