<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use App\Http\Requests\Concerns\ShopRules;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreVendorByAdminRequest extends FormRequest
{
    use ShopRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ] + $this->shopRules();
    }

    /**
     * The email may belong to an existing customer (the shop is attached to them), but not to
     * someone who already has a shop or whose account is blocked.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('email')) {
                return;
            }

            $user = User::with('vendor')->where('email', $this->input('email'))->first();

            if (! $user) {
                return;
            }

            if ($user->vendor) {
                $validator->errors()->add('email', 'This email already belongs to a user who has a shop ('.$user->vendor->shop_name.').');
                // Lets the form show a link to the existing vendor.
                session()->flash('existing_vendor_id', $user->vendor->id);

                return;
            }

            if ($user->status === UserStatus::Blocked) {
                $validator->errors()->add('email', 'This account is blocked, so a shop cannot be created for it.');
            }
        }];
    }
}
