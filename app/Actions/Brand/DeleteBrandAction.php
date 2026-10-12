<?php

namespace App\Actions\Brand;

use App\Models\Brand;
use DomainException;

class DeleteBrandAction
{
    /**
     * Soft delete: the row and its logo are kept so the brand can be restored.
     * The logo is removed when the brand is force deleted (see BrandObserver).
     *
     * @throws DomainException
     */
    public function execute(Brand $brand): void
    {
        $products = $brand->products()->count();

        if ($products > 0) {
            throw new DomainException(sprintf(
                'This brand still has %d %s. Remove or move them first.',
                $products,
                $products === 1 ? 'product' : 'products',
            ));
        }

        $brand->delete();
    }
}
