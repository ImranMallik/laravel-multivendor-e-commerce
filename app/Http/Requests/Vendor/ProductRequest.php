<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Product\ProductRequest as BaseProductRequest;
use App\Models\Product;

/**
 * Vendor create / edit: only the shared product rules. Flags and vendor_id are not part of the
 * rules, so they never reach the validated data even when someone sends them.
 */
class ProductRequest extends BaseProductRequest
{
    public function authorize(): bool
    {
        $product = $this->product();

        return $product
            ? $this->user()->can('update', $product)
            : $this->user()->can('create', Product::class);
    }
}
