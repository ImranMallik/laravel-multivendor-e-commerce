<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductFlag;
use App\Enums\VendorStatus;
use App\Http\Requests\Product\ProductRequest as BaseProductRequest;
use Illuminate\Validation\Rule;

/**
 * Admin create / edit: the shared product rules plus the home section flags and the owner
 * (an approved vendor, or empty for an in-house product).
 */
class ProductRequest extends BaseProductRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $flags = [];

        foreach (ProductFlag::columns() as $flag) {
            $flags[$flag] = $this->boolean($flag);
        }

        $this->merge($flags + [
            'vendor_id' => filled($this->input('vendor_id')) ? $this->input('vendor_id') : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules() + [
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')->where('status', VendorStatus::Approved->value)],
        ];

        foreach (ProductFlag::columns() as $flag) {
            $rules[$flag] = ['boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return parent::attributes() + ['vendor_id' => 'vendor'];
    }

    protected function flags(): ?array
    {
        return collect(ProductFlag::columns())->mapWithKeys(fn (string $flag) => [$flag => (bool) $this->validated($flag)])->all();
    }

    protected function assignsVendor(): bool
    {
        return true;
    }

    protected function vendorId(): ?int
    {
        $id = $this->validated('vendor_id');

        return $id === null ? null : (int) $id;
    }
}
