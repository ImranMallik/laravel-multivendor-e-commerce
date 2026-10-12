<?php

namespace Tests\Feature\Product;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;

/**
 * Fixtures for the variant tests: a Color attribute (Red, Blue) and a Size attribute (S, M), plus helpers
 * that build the posted rows of the product form.
 */
trait BuildsVariants
{
    use BuildsProducts;

    protected Attribute $color;

    protected Attribute $size;

    protected AttributeValue $red;

    protected AttributeValue $blue;

    protected AttributeValue $small;

    protected AttributeValue $medium;

    protected function attributes(): void
    {
        if (isset($this->color)) {
            return; // already created in this test
        }

        $this->color = Attribute::factory()->color()->create(['name' => 'Color', 'sort_order' => 1]);
        $this->size = Attribute::factory()->create(['name' => 'Size', 'sort_order' => 2]);

        $this->red = AttributeValue::factory()->create(['attribute_id' => $this->color->id, 'value' => 'Red', 'color_code' => '#ff0000', 'sort_order' => 0]);
        $this->blue = AttributeValue::factory()->create(['attribute_id' => $this->color->id, 'value' => 'Blue', 'color_code' => '#0000ff', 'sort_order' => 1]);
        $this->small = AttributeValue::factory()->create(['attribute_id' => $this->size->id, 'value' => 'S', 'sort_order' => 0]);
        $this->medium = AttributeValue::factory()->create(['attribute_id' => $this->size->id, 'value' => 'M', 'sort_order' => 1]);
    }

    /**
     * One posted variant row.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function row(AttributeValue $color, AttributeValue $size, array $overrides = []): array
    {
        return array_merge([
            'sku' => 'GP-100-'.$color->value.'-'.$size->value,
            'values' => [$this->color->id => $color->id, $this->size->id => $size->id],
            'price' => '100',
            'offer_price' => '',
            'stock_qty' => 5,
            'is_active' => 1,
        ], $overrides);
    }

    /**
     * A full product payload with variants: red/blue x S/M unless other rows are given. The first row is the default.
     *
     * @param  array<int, array<string, mixed>>|null  $rows
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function variantPayload(?array $rows = null, array $overrides = []): array
    {
        $this->attributes();

        $rows ??= [
            $this->row($this->red, $this->small),
            $this->row($this->red, $this->medium, ['price' => '110']),
            $this->row($this->blue, $this->small, ['price' => '120', 'stock_qty' => 0]),
            $this->row($this->blue, $this->medium, ['price' => '130']),
        ];

        return $this->payload(array_merge([
            'has_variants' => 1,
            'price' => '',
            'stock_qty' => '',
            'variants' => $rows,
            'variant_default' => 0,
        ], $overrides));
    }

    /**
     * The same rows as stored on a product (for edit requests): ids and values taken from the database.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function storedRows(Product $product): array
    {
        return $product->variants()->with('values')->get()->map(fn ($variant) => [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'values' => $variant->values->pluck('id', 'attribute_id')->all(),
            'price' => (string) $variant->price,
            'offer_price' => $variant->offer_price === null ? '' : (string) $variant->offer_price,
            'stock_qty' => $variant->stock_qty,
            'is_active' => $variant->is_active ? 1 : 0,
        ])->values()->all();
    }
}
