<?php

namespace App\Data;

use Illuminate\Http\UploadedFile;

/**
 * Validated product input, built by the admin and vendor Form Requests and handed to ProductService.
 * Admin-only parts (flags, vendor assignment) are simply null when a vendor submits.
 */
final class ProductData
{
    /**
     * @param  array<string, mixed>  $attributes  fillable product columns
     * @param  array<int, array{key: string, value: string}>  $specifications
     * @param  array<int, UploadedFile>  $gallery  new gallery images
     * @param  array<int, int>|null  $galleryOrder  existing gallery image ids in display order
     * @param  array<string, bool>|null  $flags  home section flags (admin only)
     * @param  bool  $assignVendor  whether vendorId should be applied (admin only)
     * @param  bool  $hasVariants  the product is sold through variants (its own price and stock are then ignored)
     * @param  array<int, array{id: int|null, sku: string, value_ids: array<int, int>, price: string, offer_price: string|null, stock_qty: int, is_active: bool, is_default: bool, image: UploadedFile|null, remove_image: bool}>  $variants
     */
    public function __construct(
        public readonly array $attributes,
        public readonly array $specifications = [],
        public readonly ?UploadedFile $thumbnail = null,
        public readonly array $gallery = [],
        public readonly ?array $galleryOrder = null,
        public readonly ?array $flags = null,
        public readonly bool $assignVendor = false,
        public readonly ?int $vendorId = null,
        public readonly bool $hasVariants = false,
        public readonly array $variants = [],
    ) {}
}
