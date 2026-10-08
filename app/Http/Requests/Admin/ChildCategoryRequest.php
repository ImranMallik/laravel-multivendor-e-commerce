<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChildCategoryRequest extends FormRequest
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
        $current = $this->route('child_category');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            // The sub category must belong to the chosen category.
            'sub_category_id' => [
                'required', 'integer',
                Rule::exists('sub_categories', 'id')
                    ->where(fn ($query) => $query->where('category_id', $this->input('category_id'))->whereNull('deleted_at')),
            ],
            // Unique inside the chosen sub category only.
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('child_categories', 'name')
                    ->where(fn ($query) => $query->where('sub_category_id', $this->input('sub_category_id'))->whereNull('deleted_at'))
                    ->ignore($current?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sub_category_id.exists' => 'The selected sub category does not belong to the selected category.',
        ];
    }
}
