<?php

namespace App\Http\Requests\Admin;

class UpdateBrandRequest extends BrandRequest
{
    protected function logoRequirement(): string
    {
        return 'nullable';
    }
}
