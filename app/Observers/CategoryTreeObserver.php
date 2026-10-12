<?php

namespace App\Observers;

use App\Models\Category;
use App\Repositories\ProductRepository;
use App\Services\CategoryMenuService;
use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared by Category, SubCategory and ChildCategory: any change clears the cached menu tree and the
 * cached home products (a deactivated category hides its products).
 */
class CategoryTreeObserver
{
    public function __construct(
        private readonly CategoryMenuService $menu,
        private readonly ImageUploadService $images,
        private readonly ProductRepository $products,
    ) {}

    public function saved(Model $model): void
    {
        $this->menu->flush();
        $this->products->flush();
    }

    public function deleted(Model $model): void
    {
        $this->menu->flush();
        $this->products->flush();
    }

    public function restored(Model $model): void
    {
        $this->menu->flush();
        $this->products->flush();
    }

    /**
     * A soft delete keeps a category's image so it can be restored; a force delete removes it.
     */
    public function forceDeleted(Model $model): void
    {
        if ($model instanceof Category) {
            $this->images->delete($model->image);
        }

        $this->menu->flush();
        $this->products->flush();
    }
}
