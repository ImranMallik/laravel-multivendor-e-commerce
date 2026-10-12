<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules for creating and updating a brand. Only the logo rule differs
 * (required on create, optional on update), see Store/UpdateBrandRequest.
 */
abstract class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // An unchecked switch sends nothing (the form also sends a hidden 0).
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $brand = $this->route('brand');

        return [
            'name' => [
                'required', 'string', 'max:100',
                // Soft-deleted brands do not block a name; the current brand is ignored on update.
                Rule::unique('brands', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($brand instanceof Brand ? $brand->getKey() : null),
            ],
            'logo' => [$this->logoRequirement(), 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    abstract protected function logoRequirement(): string;
}
