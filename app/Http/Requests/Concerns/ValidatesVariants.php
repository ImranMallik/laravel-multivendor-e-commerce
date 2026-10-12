<?php

namespace App\Http\Requests\Concerns;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Variant rules shared by the admin and vendor product requests (used by ProductRequest).
 *
 * Posted shape: has_variants, variant_default (the key of the default row) and
 * variants[key][id|sku|price|offer_price|stock_qty|is_active|image|values[attributeId] = valueId].
 */
trait ValidatesVariants
{
    protected function usesVariants(): bool
    {
        return $this->boolean('has_variants');
    }

    /**
     * Cleans the posted variant rows (blank numbers, default flag) before validation.
     *
     * @return array<string, mixed>
     */
    protected function normalizedVariantInput(): array
    {
        $rows = [];

        foreach ((array) $this->input('variants', []) as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[$key] = [
                'id' => filled($row['id'] ?? null) ? $row['id'] : null,
                'sku' => trim((string) ($row['sku'] ?? '')),
                'values' => array_filter((array) ($row['values'] ?? []), fn ($value) => filled($value)),
                'price' => $row['price'] ?? null,
                'offer_price' => filled($row['offer_price'] ?? null) ? $row['offer_price'] : null,
                'stock_qty' => $row['stock_qty'] ?? null,
                'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_default' => (string) $key === (string) $this->input('variant_default'),
                'remove_image' => filter_var($row['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return [
            'has_variants' => $this->usesVariants(),
            'variants' => $this->usesVariants() ? $rows : [],
        ];
    }

    /**
     * Field rules; they apply only while "has variants" is on.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function variantRules(): array
    {
        if (! $this->usesVariants()) {
            return ['has_variants' => ['boolean']];
        }

        return [
            'has_variants' => ['boolean'],
            'variants' => ['required', 'array', 'min:1', 'max:200'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'variants.*.values' => ['required', 'array', 'min:1'],
            'variants.*.values.*' => ['integer', Rule::exists('attribute_values', 'id')],
            'variants.*.price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'variants.*.offer_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'lt:variants.*.price'],
            'variants.*.stock_qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.is_active' => ['boolean'],
            'variants.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function variantMessages(): array
    {
        return [
            'variants.required' => 'Add at least one variant.',
            'variants.min' => 'Add at least one variant.',
            'variants.*.sku.required' => 'Each variant needs a SKU.',
            'variants.*.sku.distinct' => 'Variant SKUs must be different from each other.',
            'variants.*.price.required' => 'Each variant needs a price.',
            'variants.*.offer_price.lt' => 'A variant offer price must be lower than its price.',
            'variants.*.stock_qty.required' => 'Each variant needs a stock quantity.',
        ];
    }

    /**
     * Rules that need the database or compare rows with each other.
     */
    protected function validateVariantRows(Validator $validator): void
    {
        if (! $this->usesVariants()) {
            return;
        }

        // The combination checks need clean rows: stop at the first field error.
        foreach ($validator->errors()->keys() as $key) {
            if (str_starts_with($key, 'variants')) {
                return;
            }
        }

        $rows = (array) $this->input('variants', []);
        $product = $this->route('product');
        $product = $product instanceof Product ? $product : null;

        $values = AttributeValue::with('attribute')
            ->whereIn('id', collect($rows)->pluck('values')->flatten()->unique()->all())
            ->get()
            ->keyBy('id');

        $stored = $product
            ? $product->variants()->withTrashed()->get()->keyBy('id')
            : collect();
        $storedByHash = $stored->keyBy('combination_hash');

        $expectedAttributes = null;
        $seenHashes = [];
        $reuse = []; // row key => id of the stored variant that row reuses (its own SKU is not a clash)
        $active = 0;
        $defaults = 0;
        $defaultIsActive = false;

        foreach ($rows as $key => $row) {
            $valueIds = array_map('intval', array_values($row['values']));
            $hash = ProductVariant::combinationHash($valueIds);
            $attributeIds = array_map('intval', array_keys($row['values']));
            sort($attributeIds);

            // Each posted value really belongs to the attribute it is posted under.
            foreach ($row['values'] as $attributeId => $valueId) {
                if ((int) $values[(int) $valueId]->attribute_id !== (int) $attributeId) {
                    $validator->errors()->add("variants.$key.values", 'A chosen value does not belong to its attribute.');

                    continue 2;
                }
            }

            // One value per attribute, and every variant uses the same attributes.
            $expectedAttributes ??= $attributeIds;

            if ($attributeIds !== $expectedAttributes) {
                $validator->errors()->add("variants.$key.values", 'Every variant needs exactly one value for each attribute.');
            }

            if (isset($seenHashes[$hash])) {
                $validator->errors()->add("variants.$key.values", 'This combination is listed twice.');
            }
            $seenHashes[$hash] = true;

            // An existing variant keeps its combination and must belong to this product.
            $unchanged = false;

            if ($row['id'] !== null) {
                $existing = $stored->get((int) $row['id']);

                if (! $existing) {
                    $validator->errors()->add("variants.$key.id", 'This variant does not belong to the product.');
                } elseif ($existing->combination_hash !== $hash) {
                    $validator->errors()->add("variants.$key.values", 'The combination of an existing variant cannot be changed; remove it and add a new one.');
                } else {
                    $unchanged = true;
                    $reuse[$key] = $existing->id;
                }
            } elseif ($storedByHash->has($hash)) {
                $reuse[$key] = $storedByHash->get($hash)->id; // a removed combination is restored
            }

            // New combinations may only use active attributes and values (an unchanged variant keeps what it has).
            if (! $unchanged) {
                foreach ($valueIds as $valueId) {
                    $value = $values[$valueId];

                    if (! $value->is_active || ! $value->attribute?->is_active) {
                        $validator->errors()->add("variants.$key.values", '"'.$value->value.'" is not available. Choose active values only.');
                    }
                }
            }

            $active += $row['is_active'] ? 1 : 0;
            $defaults += $row['is_default'] ? 1 : 0;
            $defaultIsActive = $defaultIsActive || ($row['is_default'] && $row['is_active']);
        }

        if ($active === 0) {
            $validator->errors()->add('variants', 'At least one variant must be active.');
        }

        if ($defaults !== 1) {
            $validator->errors()->add('variant_default', 'Choose exactly one default variant.');
        } elseif (! $defaultIsActive) {
            $validator->errors()->add('variant_default', 'The default variant must be active.');
        }

        $this->validateVariantSkus($validator, $rows, $reuse);
    }

    /**
     * Variant SKUs are unique across all products and variants (the product's own SKU included).
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     * @param  array<int|string, int>  $reuse
     */
    private function validateVariantSkus(Validator $validator, array $rows, array $reuse): void
    {
        $skus = collect($rows)->pluck('sku')->all();

        $variantOwners = ProductVariant::withTrashed()->whereIn('sku', $skus)->pluck('id', 'sku');
        $productSkus = Product::withTrashed()->whereIn('sku', $skus)->pluck('id', 'sku');

        foreach ($rows as $key => $row) {
            $ownerId = $variantOwners->get($row['sku']);
            $clashWithVariant = $ownerId !== null && ($reuse[$key] ?? null) !== $ownerId;

            if ($clashWithVariant || $productSkus->has($row['sku']) || strcasecmp($row['sku'], (string) $this->input('sku')) === 0) {
                $validator->errors()->add("variants.$key.sku", 'The SKU "'.$row['sku'].'" is already used by another product or variant.');
            }
        }
    }
}
