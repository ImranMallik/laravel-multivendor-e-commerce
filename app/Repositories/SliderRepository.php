<?php

namespace App\Repositories;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SliderRepository
{
    public const CACHE_KEY = 'frontend.sliders';

    /**
     * Active slides for the home banner, cached until a slider changes (see SliderObserver).
     *
     * @return Collection<int, Slider>
     */
    public function active(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Slider::active()->ordered()->get());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
