<?php

namespace App\Actions\Category;

use App\Models\Category;
use DomainException;

class DeleteCategoryAction
{
    /**
     * Soft delete, unless the category still has sub categories.
     *
     * @throws DomainException
     */
    public function execute(Category $category): void
    {
        $subCategories = $category->subCategories()->count();

        if ($subCategories > 0) {
            throw new DomainException(sprintf(
                'This category still has %d %s. Remove or move them first.',
                $subCategories,
                $subCategories === 1 ? 'sub category' : 'sub categories',
            ));
        }

        // TODO: also block deleting a category that still has products once products exist.
        $category->delete();
    }
}
