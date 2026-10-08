<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The one place that builds the storefront category menu.
 *
 * Only active categories shown in the menu are included, each with its active sub categories and
 * their active child categories, ordered by sort_order. An inactive level hides everything below it
 * because descendants are only loaded through active parents. The tree is cached as plain arrays and
 * flushed by CategoryTreeObserver whenever any level changes.
 */
class CategoryMenuService
{
    public const CACHE_KEY = 'frontend.category_menu';

    /**
     * @return Collection<int, array{id: int, name: string, slug: string, icon: string, subs: array<int, array{id: int, name: string, slug: string, children: array<int, array{id: int, name: string, slug: string}>}>}>
     */
    public function tree(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => $this->build());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): Collection
    {
        return Category::query()
            ->active()
            ->inMenu()
            ->ordered()
            ->select(['id', 'name', 'slug', 'icon'])
            ->with([
                'subCategories' => fn ($query) => $query->active()->ordered()->select(['id', 'category_id', 'name', 'slug']),
                'subCategories.childCategories' => fn ($query) => $query->active()->ordered()->select(['id', 'sub_category_id', 'name', 'slug']),
            ])
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon_class,
                'subs' => $category->subCategories->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'slug' => $sub->slug,
                    'children' => $sub->childCategories->map(fn ($child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                    ])->all(),
                ])->all(),
            ])
            ->values();
    }
}
