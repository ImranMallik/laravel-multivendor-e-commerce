<?php

namespace App\Repositories;

use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogTag;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront blog queries. The home posts and the sidebar widgets are cached and cleared by
 * BlogCacheObserver; the cache also expires when the next scheduled post goes live, so a scheduled
 * post appears on time without anyone clearing it.
 */
class BlogRepository
{
    public const HOME_CACHE_KEY = 'frontend.blog.home';

    public const SIDEBAR_CACHE_KEY = 'frontend.blog.sidebar';

    /** Posts in the home slider (the template's slider shows 4 at a time). */
    public const HOME_LIMIT = 8;

    /** Listing page: the grid is 4 / 3 / 2 / 1 per row, 12 fills every layout. */
    public const PER_PAGE = 12;

    public const COMMENTS_PER_PAGE = 10;

    /**
     * @return Collection<int, Blog>
     */
    public function homePosts(): Collection
    {
        return Cache::remember(self::HOME_CACHE_KEY, fn () => $this->ttl(), fn () => $this->visible()
            ->where('blogs.show_on_home', true)
            ->ordered()
            ->limit(self::HOME_LIMIT)
            ->get());
    }

    /**
     * Sidebar widgets: categories that have published posts, the most viewed posts, and the tags used most.
     *
     * @return array{categories: Collection<int, BlogCategory>, popular: Collection<int, Blog>, tags: Collection<int, BlogTag>}
     */
    public function sidebar(): array
    {
        return Cache::remember(self::SIDEBAR_CACHE_KEY, fn () => $this->ttl(), function () {
            $visible = fn (Builder $blog) => $blog->published();

            return [
                'categories' => BlogCategory::active()->whereHas('blogs', $visible)->ordered()->get(['id', 'name', 'slug']),
                'popular' => Blog::query()->visible()
                    ->withCount('approvedComments')
                    ->orderByDesc('views')->ordered()
                    ->limit(5)
                    ->get(['id', 'title', 'slug', 'image', 'published_at', 'created_at', 'blog_category_id']),
                'tags' => BlogTag::query()
                    ->whereHas('blogs', fn (Builder $blog) => $blog->visible())
                    ->withCount(['blogs' => fn (Builder $blog) => $blog->visible()])
                    ->orderByDesc('blogs_count')->orderBy('name')
                    ->limit(12)
                    ->get(['id', 'name', 'slug']),
            ];
        });
    }

    /**
     * Published posts, newest first; optional search (title / content), category or tag.
     *
     * @return LengthAwarePaginator<Blog>
     */
    public function paginate(?string $search = null, ?BlogCategory $category = null, ?BlogTag $tag = null): LengthAwarePaginator
    {
        return $this->visible()
            ->when(filled($search), fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('blogs.title', 'like', '%'.$search.'%')
                ->orWhere('blogs.content', 'like', '%'.$search.'%')))
            ->when($category, fn (Builder $query) => $query->where('blogs.blog_category_id', $category->id))
            ->when($tag, fn (Builder $query) => $query->whereHas('tags', fn (Builder $t) => $t->whereKey($tag->id)))
            ->ordered()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * A published post with everything its page shows; 404 otherwise (drafts, scheduled, deleted, inactive category).
     */
    public function findVisibleBySlug(string $slug): Blog
    {
        return $this->forPage(Blog::query()->visible()->where('blogs.slug', $slug))->firstOrFail();
    }

    /**
     * Any post (draft or scheduled too) for the admin's signed preview.
     */
    public function findForPreview(int $id): Blog
    {
        return $this->forPage(Blog::query()->whereKey($id))->firstOrFail();
    }

    /**
     * @return Collection<int, Blog>
     */
    public function related(Blog $blog, int $limit = 6): Collection
    {
        return $this->visible()
            ->where('blogs.blog_category_id', $blog->blog_category_id)
            ->whereKeyNot($blog->id)
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * Approved top-level comments with their approved replies, paginated (?comments_page=).
     *
     * @return LengthAwarePaginator<BlogComment>
     */
    public function comments(Blog $blog): LengthAwarePaginator
    {
        return $blog->comments()
            ->approved()
            ->topLevel()
            ->with(['user', 'replies' => fn ($query) => $query->approved()->with('user')->oldest()])
            ->oldest()
            ->paginate(self::COMMENTS_PER_PAGE, ['*'], 'comments_page')
            ->withQueryString();
    }

    public function flush(): void
    {
        Cache::forget(self::HOME_CACHE_KEY);
        Cache::forget(self::SIDEBAR_CACHE_KEY);
    }

    /**
     * Visible posts with what a card needs (category label).
     *
     * @return Builder<Blog>
     */
    private function visible(): Builder
    {
        return Blog::query()->visible()->with('category:id,name,slug');
    }

    /**
     * @param  Builder<Blog>  $query
     * @return Builder<Blog>
     */
    private function forPage(Builder $query): Builder
    {
        return $query->with(['category:id,name,slug,is_active', 'author:id,name', 'tags:id,name,slug'])
            ->withCount('approvedComments');
    }

    /**
     * Cache lifetime: one hour, or until the next scheduled post goes live if that is sooner
     * (passed as a closure, so it is only worked out when the cache is rebuilt).
     */
    private function ttl(): CarbonInterface
    {
        $next = Blog::query()
            ->where('status', BlogStatus::Published->value)
            ->where('published_at', '>', now())
            ->min('published_at');

        $hour = now()->addHour();

        return $next && now()->parse($next)->lt($hour) ? now()->parse($next) : $hour;
    }
}
