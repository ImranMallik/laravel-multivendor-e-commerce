<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * What the product form's Variants section needs, for both panels: the active attributes to choose
 * from, and the rows to show (the product's variants, or the rows that were just posted after a
 * validation error).
 */
class ProductVariantFormData
{
    /**
     * Active attributes with their active values (what a seller may pick from).
     *
     * @return Collection<int, Attribute>
     */
    public function attributes(): Collection
    {
        return Attribute::active()->ordered()->with('activeValues')->get()->filter(fn (Attribute $a) => $a->activeValues->isNotEmpty())->values();
    }

    /**
     * @param  array<int|string, array<string, mixed>>|null  $old  the posted rows (old input), if the form is being redisplayed
     * @return array<int|string, array<string, mixed>>
     */
    public function rows(?Product $product, ?array $old): array
    {
        return $old !== null && $old !== [] ? $this->fromInput($old) : $this->fromProduct($product);
    }

    /**
     * Identical for the key the form posts (variant_default): the old default, else the stored default.
     */
    public function defaultKey(array $rows): string|int|null
    {
        foreach ($rows as $key => $row) {
            if ($row['is_default']) {
                return $key;
            }
        }

        return array_key_first($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fromProduct(?Product $product): array
    {
        if (! $product || ! $product->exists) {
            return [];
        }

        return $product->variants()->with('values.attribute')->get()->map(fn ($variant) => [
            'id' => $variant->id,
            'label' => $variant->label,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'offer_price' => $variant->offer_price,
            'stock_qty' => $variant->stock_qty,
            'is_active' => $variant->is_active,
            'is_default' => $variant->is_default,
            'image_url' => $variant->hasImage() ? $variant->image_url : null,
            'values' => $variant->values->pluck('id', 'attribute_id')->all(),
        ])->values()->all();
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $input
     * @return array<int|string, array<string, mixed>>
     */
    private function fromInput(array $input): array
    {
        $ids = collect($input)->pluck('values')->filter()->flatten()->unique()->all();
        $values = AttributeValue::whereIn('id', $ids)->get()->keyBy('id');
        $default = request()->old('variant_default');
        $rows = [];

        foreach ($input as $key => $row) {
            $map = array_filter((array) ($row['values'] ?? []), fn ($id) => filled($id));

            $rows[$key] = [
                'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                'label' => collect($map)->map(fn ($id) => $values[(int) $id]->value ?? '?')->implode(' / '),
                'sku' => (string) ($row['sku'] ?? ''),
                'price' => $row['price'] ?? null,
                'offer_price' => $row['offer_price'] ?? null,
                'stock_qty' => $row['stock_qty'] ?? 0,
                'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_default' => (string) $key === (string) $default,
                'image_url' => null,
                'values' => $map,
            ];
        }

        return $rows;
    }
}
