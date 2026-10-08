<?php

namespace App\Actions\Category;

use App\Models\ChildCategory;

class UpdateChildCategoryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(ChildCategory $childCategory, array $data): ChildCategory
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $childCategory->update($data);

        return $childCategory;
    }
}
