<?php

namespace App\Http\Requests\Admin;

use App\Enums\AttributeType;
use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / edit an attribute together with its values (one form).
 */
class AttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $isColor = $this->input('type') === AttributeType::Color->value;

        // Drop the blank value rows the form may send; colors only exist for Color attributes.
        $values = collect((array) $this->input('values', []))
            ->filter(fn ($row) => is_array($row) && filled($row['value'] ?? null))
            ->map(fn (array $row) => [
                'id' => filled($row['id'] ?? null) ? $row['id'] : null,
                'value' => trim((string) $row['value']),
                'color_code' => $isColor && filled($row['color_code'] ?? null) ? strtolower((string) $row['color_code']) : null,
                'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->values()
            ->all();

        $this->merge([
            'values' => $values,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $attribute = $this->route('attribute');
        $isColor = $this->input('type') === AttributeType::Color->value;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('attributes', 'name')->ignore($attribute instanceof Attribute ? $attribute->getKey() : null)],
            'type' => ['required', Rule::enum(AttributeType::class)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
            'values' => ['required', 'array', 'min:1', 'max:200'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.value' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'values.*.color_code' => $isColor ? ['required', 'regex:/^#[0-9a-fA-F]{6}$/'] : ['nullable'],
            'values.*.is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'values.required' => 'Add at least one value.',
            'values.min' => 'Add at least one value.',
            'values.*.value.distinct' => 'Each value must be different (for example S, M, L).',
            'values.*.color_code.required' => 'Pick a color for every value of a Color attribute.',
            'values.*.color_code.regex' => 'A color must look like #1c93cb.',
        ];
    }
}
