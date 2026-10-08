<?php

namespace App\Actions\Category;

use App\Models\ChildCategory;

class DeleteChildCategoryAction
{
    public function execute(ChildCategory $childCategory): void
    {
        // TODO: also block deleting a child category that still has products once products exist.
        $childCategory->delete();
    }
}
