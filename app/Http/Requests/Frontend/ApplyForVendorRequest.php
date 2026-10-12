<?php

namespace App\Http\Requests\Frontend;

use App\Http\Requests\Concerns\ShopRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shop application of a signed-in user. Used for the first application and for a re-application
 * after a rejection (the shop name may stay unchanged then).
 */
class ApplyForVendorRequest extends FormRequest
{
    use ShopRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->shopRules($this->user()->vendor);
    }
}
