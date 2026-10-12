<?php

namespace App\Http\Requests\Frontend;

use App\Http\Requests\Concerns\ShopRules;

class VendorRegisterRequest extends RegisterRequest
{
    use ShopRules;

    /**
     * Same account fields as a customer, plus the shop details. The account is always a customer
     * account; the shop starts as a pending application.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + $this->shopRules(withLogo: false);
    }
}
