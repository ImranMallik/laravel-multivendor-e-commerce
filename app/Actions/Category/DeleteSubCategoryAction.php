<?php

namespace App\Actions\Category;

use App\Models\SubCategory;
use DomainException;

class DeleteSubCategoryAction
{
    /**
     * Soft delete, unless the sub category still has child categories.
     *
     * @throws DomainException
     */
    public function execute(SubCategory $subCategory): void
    {
        $children = $subCategory->childCategories()->count();

        if ($children > 0) {
            throw new DomainException(sprintf(
                'This sub category still has %d child %s. Remove or move them first.',
                $children,
                $children === 1 ? 'category' : 'categories',
            ));
        }

        // TODO: also block deleting a sub category that still has products once products exist.
        $subCategory->delete();
    }
}
