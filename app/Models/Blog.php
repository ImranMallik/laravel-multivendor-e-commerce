<?php

namespace App\Models;

use App\Enums\BlogStatus;
use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * A blog post. Visible on the storefront only when published() (status Published and published_at
 * reached); a Published post with a future date is "Scheduled". content is purified HTML (BlogService).
 */
class Blog extends Model
{
    use HasFactory, SoftDeletes;

    /** The template's details image is 1210 x 637; the cards show the same picture smaller. */
    public const IMAGE_HINT = 'Recommended size: 1210 x 637 px. JPG, PNG or WEBP, up to 2 MB.';

    public const IMAGE_FOLDER = 'blogs';

    public const CONTENT_IMAGE_FOLDER = 'blogs/content';

    protected $fillable = [
        'blog_category_id',
        'admin_id',
        'title',
        'slug',
        'image',
        'excerpt',
        'content',
        'seo_title',
        'seo_description',
        'status',
        'published_at',
        'show_on_home',
    ];

    protected $casts = [
        'status' => BlogStatus::class,
        'published_at' => 'datetime',
        'show_on_home' => 'boolean',
        'views' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id')->withTrashed();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', CommentStatus::Approved->value);
    }

    /**
     * Status Published and the publish time reached (scheduled posts and drafts are excluded).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('blogs.status', BlogStatus::Published->value)
            ->whereNotNull('blogs.published_at')
            ->where('blogs.published_at', '<=', now());
    }

    /**
     * Newest first.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('blogs.published_at')->orderByDesc('blogs.id');
    }

    /**
     * Published posts whose category is active (what the storefront lists).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()->whereHas('category', fn (Builder $category) => $category->active());
    }

    public function isPublished(): bool
    {
        return $this->status === BlogStatus::Published && $this->published_at !== null && $this->published_at->lte(now());
    }

    /**
     * Admin label: Draft, Published or Scheduled (published with a future date).
     *
     * @return array{label: string, color: string}
     */
    public function displayState(): array
    {
        return match (true) {
            $this->status === BlogStatus::Draft => ['label' => 'Draft', 'color' => 'secondary'],
            $this->published_at?->isFuture() => ['label' => 'Scheduled', 'color' => 'info'],
            default => ['label' => 'Published', 'color' => 'success'],
        };
    }

    public function hasImage(): bool
    {
        return $this->image && Storage::disk('public')->exists($this->image);
    }

    /**
     * Uploaded image, or the template's blog picture as a placeholder.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasImage()
            ? asset('storage/'.$this->image)
            : asset('frontend-assets/images/blog_details.jpg'));
    }

    /**
     * Template date style, e.g. "nov 04 2021".
     */
    protected function formattedDate(): Attribute
    {
        return Attribute::get(fn () => strtolower(($this->published_at ?? $this->created_at)?->format('M d Y') ?? ''));
    }

    protected function authorName(): Attribute
    {
        return Attribute::get(fn () => $this->author?->name ?? 'admin');
    }

    /**
     * Meta description: the SEO description, else the excerpt.
     */
    protected function metaDescription(): Attribute
    {
        return Attribute::get(fn () => $this->seo_description ?: (string) $this->excerpt);
    }
}
