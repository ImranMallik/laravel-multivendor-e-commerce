<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the DataTables server-side request for the sliders list (no page filters).
 */
class SliderTableRequest extends FormRequest
{
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
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'search.value' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'array', 'max:1'],
            'order.*.column' => ['required_with:order', 'integer', 'min:0', 'max:20'],
            'order.*.dir' => ['required_with:order', Rule::in(['asc', 'desc'])],
        ];
    }
}
