<?php

namespace App\Http\Requests\Concerns;

use App\Models\Vendor;
use Illuminate\Validation\Rule;

/**
 * Shop field rules shared by the admin "Add Vendor" form, the account application form and the signup form.
 */
trait ShopRules
{
    /**
     * @param  Vendor|null  $ignore  the vendor being edited (a re-application keeps its own shop name)
     * @return array<string, array<int, mixed>>
     */
    protected function shopRules(?Vendor $ignore = null, bool $withLogo = true): array
    {
        $rules = [
            'shop_name' => ['required', 'string', 'max:150', Rule::unique('vendors', 'shop_name')->ignore($ignore?->getKey())],
            'shop_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
        ];

        if ($withLogo) {
            $rules['logo'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'];
        }

        return $rules;
    }
}
