<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $current = $this->route('sub_category');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            // Unique inside the chosen category only.
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('sub_categories', 'name')
                    ->where(fn ($query) => $query->where('category_id', $this->input('category_id'))->whereNull('deleted_at'))
                    ->ignore($current?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }
}
