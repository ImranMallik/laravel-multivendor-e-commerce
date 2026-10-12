<?php

namespace App\Observers;

use App\Models\Brand;
use App\Repositories\BrandRepository;
use App\Services\ImageUploadService;

class BrandObserver
{
    public function __construct(
        private readonly BrandRepository $brands,
        private readonly ImageUploadService $images,
    ) {}

    public function saved(Brand $brand): void
    {
        $this->brands->flush();
    }

    public function deleted(Brand $brand): void
    {
        $this->brands->flush();
    }

    public function restored(Brand $brand): void
    {
        $this->brands->flush();
    }

    /**
     * A soft delete keeps the logo so the brand can be restored; a force delete removes it.
     */
    public function forceDeleted(Brand $brand): void
    {
        $this->images->delete($brand->logo);
        $this->brands->flush();
    }
}
