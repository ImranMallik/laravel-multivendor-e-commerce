<?php

namespace App\Http\Requests\Frontend;

class VendorRegisterRequest extends RegisterRequest
{
    /**
     * Same account fields as a customer, plus the shop details.
     * The role is decided by the route, not by any input.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'shop_name' => ['required', 'string', 'max:100'],
            'shop_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }
}
