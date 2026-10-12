<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\Admin\TableRequest;
use App\Models\Product;

/**
 * DataTables request for the vendor's product list (same paging / search / order rules as the admin lists).
 */
class ProductTableRequest extends TableRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }
}
