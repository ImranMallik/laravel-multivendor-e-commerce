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
        // TODO: enable once products exist (products.brand_id) and Brand::products() is added.
        // if ($brand->products()->exists()) {
        //     throw new DomainException('This brand still has products. Remove or move them first.');
        // }

        $brand->delete();
    }
}
