<?php

namespace App\View\Composers;

use App\Enums\VendorStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Vendor;
use App\Services\ProductVariantFormData;
use Illuminate\View\View;

/**
 * Options for the product form (admin and vendor) and the admin product filters. Only admin views get the vendor list.
 */
class ProductFormComposer
{
    public function __construct(private readonly ProductVariantFormData $variants) {}

    public function compose(View $view): void
    {
        $view->with([
            'categoryOptions' => Category::active()->ordered()->pluck('name', 'id')->all(),
            'brandOptions' => Brand::active()->orderBy('name')->pluck('name', 'id')->all(),
        ]);

        // Variants section of the create / edit forms (not needed by the admin list's filters).
        if ($view->name() !== 'admin.products.index') {
            $product = $view->getData()['product'] ?? null;
            $rows = $this->variants->rows($product, request()->old('variants'));

            $view->with([
                'variantAttributes' => $this->variants->attributes(),
                'variantRows' => $rows,
                'variantDefault' => $this->variants->defaultKey($rows),
            ]);
        }

        // The filter lists every vendor; a product can only be assigned to an approved one.
        if ($view->name() === 'admin.products.index') {
            $view->with('vendorOptions', Vendor::orderBy('shop_name')->pluck('shop_name', 'id')->all());
        } elseif (str_starts_with($view->name(), 'admin.')) {
            $view->with('vendorOptions', Vendor::where('status', VendorStatus::Approved->value)->orderBy('shop_name')->pluck('shop_name', 'id')->all());
        }
    }
}
