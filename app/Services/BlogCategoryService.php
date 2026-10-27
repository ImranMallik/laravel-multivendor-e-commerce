<?php

namespace App\Services;

use App\Models\BlogCategory;
use DomainException;

/**
 * Admin writes for blog categories.
 */
class BlogCategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogCategory
    {
        return BlogCategory::create($data + ['sort_order' => 0]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogCategory $category, array $data): BlogCategory
    {
        $category->update($data);

        return $category;
    }

    public function toggleActive(BlogCategory $category): BlogCategory
    {
        $category->update(['is_active' => ! $category->is_active]);

        return $category;
    }

    /**
     * @throws DomainException when posts still use the category
     */
    public function delete(BlogCategory $category): void
    {
        $count = $category->blogs()->count();

        if ($count > 0) {
            throw new DomainException("“{$category->name}” still has {$count} ".str('post')->plural($count).'. Move or delete them first.');
        }

        $category->delete();
    }
}
