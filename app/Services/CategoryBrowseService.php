<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;

/**
 * Resolves a storefront category URL into the data its page needs.
 *
 * Every level in the URL must be active and must really belong to the one above it, so an
 * inactive level (or a mismatched slug) is a 404, and so is everything beneath an inactive level.
 */
class CategoryBrowseService
{
    /**
     * @return array{title: string, trail: array<string, string|null>, links: array<string, string>}
     */
    public function category(string $categorySlug): array
    {
        $category = $this->findCategory($categorySlug);

        return [
            'title' => $category->name,
            'trail' => [$category->name => null],
            'links' => $category->subCategories()->active()->ordered()->get(['id', 'category_id', 'name', 'slug'])
                ->mapWithKeys(fn (SubCategory $sub) => [$sub->name => route('category.sub.show', [$category->slug, $sub->slug])])
                ->all(),
        ];
    }

    /**
     * @return array{title: string, trail: array<string, string|null>, links: array<string, string>}
     */
    public function subCategory(string $categorySlug, string $subSlug): array
    {
        $category = $this->findCategory($categorySlug);
        $sub = $this->findSubCategory($category, $subSlug);

        return [
            'title' => $sub->name,
            'trail' => [
                $category->name => route('category.show', $category->slug),
                $sub->name => null,
            ],
            'links' => $sub->childCategories()->active()->ordered()->get(['id', 'sub_category_id', 'name', 'slug'])
                ->mapWithKeys(fn (ChildCategory $child) => [$child->name => route('category.child.show', [$category->slug, $sub->slug, $child->slug])])
                ->all(),
        ];
    }

    /**
     * @return array{title: string, trail: array<string, string|null>, links: array<string, string>}
     */
    public function childCategory(string $categorySlug, string $subSlug, string $childSlug): array
    {
        $category = $this->findCategory($categorySlug);
        $sub = $this->findSubCategory($category, $subSlug);
        $child = $sub->childCategories()->active()->where('slug', $childSlug)->firstOrFail();

        return [
            'title' => $child->name,
            'trail' => [
                $category->name => route('category.show', $category->slug),
                $sub->name => route('category.sub.show', [$category->slug, $sub->slug]),
                $child->name => null,
            ],
            'links' => [],
        ];
    }

    private function findCategory(string $slug): Category
    {
        return Category::active()->where('slug', $slug)->firstOrFail();
    }

    private function findSubCategory(Category $category, string $slug): SubCategory
    {
        return $category->subCategories()->active()->where('slug', $slug)->firstOrFail();
    }
}
