<?php

namespace App\Observers;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ImageUploadService;

/**
 * Clears the cached storefront products on every change, and removes a product's files when it is
 * force deleted (a soft delete keeps them so the product can be restored).
 */
class ProductObserver
{
    /**
     * Gallery paths collected before the rows disappear with the force delete (keyed by product id).
     * Static: Laravel resolves a new observer instance for every event.
     *
     * @var array<int, array<int, string>>
     */
    private static array $galleryPaths = [];

    public function __construct(
        private readonly ProductRepository $products,
        private readonly ImageUploadService $images,
    ) {}

    public function saved(Product $product): void
    {
        $this->products->flush();
    }

    public function deleted(Product $product): void
    {
        $this->products->flush();
    }

    public function restored(Product $product): void
    {
        $this->products->flush();
    }

    public function forceDeleting(Product $product): void
    {
        // Gallery and variant images (soft deleted variants included): their rows disappear with the product.
        self::$galleryPaths[$product->id] = array_merge(
            $product->images()->pluck('image')->all(),
            $product->variants()->withTrashed()->whereNotNull('image')->pluck('image')->all(),
        );
    }

    public function forceDeleted(Product $product): void
    {
        $this->images->delete($product->thumbnail);

        foreach (self::$galleryPaths[$product->id] ?? [] as $path) {
            $this->images->delete($path);
        }

        unset(self::$galleryPaths[$product->id]);

        $this->products->flush();
    }
}
