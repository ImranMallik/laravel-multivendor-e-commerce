<?php

namespace App\Actions\Category;

use App\Models\SubCategory;

class StoreSubCategoryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): SubCategory
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return SubCategory::create($data);
    }
}
