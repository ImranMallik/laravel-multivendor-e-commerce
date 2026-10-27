<?php

namespace App\Services;

use App\Enums\ProductFlag;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The home page's "Weekly Best Rated" and "Weekly Best Sale" lists. They are meant to rank by the last
 * 7 days of reviews and orders; neither exists yet, so both use the admin's home flags for now. Switching
 * to real data later only changes this class.
 *
 * Cached until the end of the day and cleared with the other home caches by ProductRepository::flush()
 * (called by the product, variant, vendor and category observers).
 */
class HomeProductService
{
    public const CACHE_KEY = 'frontend.home_weekly_best';

    /** Products per list (the template's slider shows 3 at a time). */
    public const LIMIT = 9;

    public function __construct(private readonly ProductRepository $products) {}

    /**
     * @return Collection<int, Product>
     */
    public function weeklyBestRated(): Collection
    {
        return $this->lists()['rated'];
    }

    /**
     * @return Collection<int, Product>
     */
    public function weeklyBestSale(): Collection
    {
        return $this->lists()['sale'];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{rated: Collection<int, Product>, sale: Collection<int, Product>}
     */
    private function lists(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->endOfDay(), fn () => [
            // TODO: switch to real ratings once reviews exist (average rating of the last 7 days of reviews).
            // Until then: products the admin flagged Featured or Top, newest first.
            'rated' => $this->products->visible()
                ->where(fn (Builder $query) => $query
                    ->where('products.'.ProductFlag::Featured->value, true)
                    ->orWhere('products.'.ProductFlag::Top->value, true))
                ->latest('products.id')
                ->limit(self::LIMIT)
                ->get(),
            // TODO: switch to real order data once orders exist (quantity sold in the last 7 days).
            // Until then: products the admin flagged Best, newest first.
            'sale' => $this->products->visible()
                ->flagged(ProductFlag::Best->value)
                ->latest('products.id')
                ->limit(self::LIMIT)
                ->get(),
        ]);
    }
}
