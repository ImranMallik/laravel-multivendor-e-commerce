<?php

namespace App\Observers;

use App\Models\Vendor;
use App\Repositories\ProductRepository;

/**
 * A vendor's status decides whether their products are on the storefront, so a status change
 * clears the cached home products.
 */
class VendorProductCacheObserver
{
    public function __construct(private readonly ProductRepository $products) {}

    public function saved(Vendor $vendor): void
    {
        if ($vendor->wasChanged('status') || $vendor->wasRecentlyCreated) {
            $this->products->flush();
        }
    }

    public function deleted(Vendor $vendor): void
    {
        $this->products->flush();
    }
}
