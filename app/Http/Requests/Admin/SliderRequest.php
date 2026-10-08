<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared rules for creating and updating a slider. Only the image rule differs
 * (required on create, optional on update), see Store/UpdateSliderRequest.
 */
abstract class SliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // An unchecked switch sends nothing (the form also sends a hidden 0).
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'top_text' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:150'],
            'offer_text' => ['nullable', 'string', 'max:150'],
            'button_text' => ['nullable', 'string', 'max:50', 'required_with:button_url'],
            'button_url' => ['nullable', 'url:http,https', 'max:255', 'required_with:button_text'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
            'image' => [$this->imageRequirement(), 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    abstract protected function imageRequirement(): string;
}
