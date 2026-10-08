<?php

namespace App\Actions\Category;

use App\Models\ChildCategory;

class StoreChildCategoryAction
{
    /**
     * category_id is taken from the sub category by the model.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): ChildCategory
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return ChildCategory::create($data);
    }
}
