<?php

namespace App\Observers;

use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared by ProductVariant, Attribute and AttributeValue: a change clears the cached storefront products.
 */
class CatalogCacheObserver
{
    public function __construct(private readonly ProductRepository $products) {}

    public function saved(Model $model): void
    {
        $this->products->flush();
    }

    public function deleted(Model $model): void
    {
        $this->products->flush();
    }

    public function restored(Model $model): void
    {
        $this->products->flush();
    }
}
