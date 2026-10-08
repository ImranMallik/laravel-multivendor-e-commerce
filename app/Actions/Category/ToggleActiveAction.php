<?php

namespace App\Actions\Category;

use Illuminate\Database\Eloquent\Model;

/**
 * Flips is_active on a category, sub category or child category.
 */
class ToggleActiveAction
{
    public function execute(Model $model): Model
    {
        $model->is_active = ! $model->is_active;
        $model->save();

        return $model;
    }
}
