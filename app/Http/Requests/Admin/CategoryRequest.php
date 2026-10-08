<?php

namespace App\Http\Requests\Admin;

use App\Rules\FontAwesomeIcon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Unchecked switches send nothing (the form also sends a hidden 0).
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'show_in_menu' => $this->boolean('show_in_menu'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $current = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->withoutTrashed()->ignore($current?->id)],
            // Must be one of the picker's icons (Font Awesome Free 5.15.1), e.g. "fas fa-tv".
            'icon' => ['nullable', 'string', 'max:100', new FontAwesomeIcon],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_image' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
            'show_in_menu' => ['boolean'],
        ];
    }
}
