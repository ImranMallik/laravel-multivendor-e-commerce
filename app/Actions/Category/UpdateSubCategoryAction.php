<?php

namespace App\Actions\Category;

use App\Models\SubCategory;

class UpdateSubCategoryAction
{
    /**
     * Moving the sub category to another category also moves its child categories (see the model).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(SubCategory $subCategory, array $data): SubCategory
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $subCategory->update($data);

        return $subCategory;
    }
}
