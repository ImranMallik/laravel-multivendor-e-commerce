<?php

namespace App\Http\Requests\Admin;

class StoreBrandRequest extends BrandRequest
{
    protected function logoRequirement(): string
    {
        return 'required';
    }
}
