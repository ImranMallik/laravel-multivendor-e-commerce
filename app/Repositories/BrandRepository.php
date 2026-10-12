<?php

namespace App\Repositories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class BrandRepository
{
    public const CACHE_KEY = 'frontend.featured_brands';

    /**
     * Active and featured brands for the home slider, cached until a brand changes (see BrandObserver).
     *
     * @return Collection<int, Brand>
     */
    public function featured(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Brand::active()->featured()->ordered()->get());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
