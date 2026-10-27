<?php

namespace App\Observers;

use App\Repositories\BlogRepository;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared by Blog, BlogCategory, BlogTag and BlogComment: any change clears the cached home posts and
 * sidebar widgets. Runs after the transaction commits, so tags synced after the post is saved are included.
 * (The views counter uses a plain UPDATE and does not come through here.)
 */
class BlogCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly BlogRepository $blogs) {}

    public function saved(Model $model): void
    {
        $this->blogs->flush();
    }

    public function deleted(Model $model): void
    {
        $this->blogs->flush();
    }

    public function restored(Model $model): void
    {
        $this->blogs->flush();
    }
}
