<?php

namespace App\Http\Requests\Product;

use App\Data\ProductData;
use App\Enums\ProductStatus;
use App\Http\Requests\Concerns\ValidatesVariants;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Product rules shared by the admin and vendor panels. The thumbnail is required when creating
 * (no {product} in the route) and optional when editing. Panel-specific parts (flags, owner)
 * are added by the admin request; the vendor request adds nothing, so those inputs are ignored.
 */
abstract class ProductRequest extends FormRequest
{
    use ValidatesVariants;

    protected function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }

    protected function prepareForValidation(): void
    {
        // Drop empty specification rows the form always sends, and normalise blank numbers.
        $rows = collect((array) $this->input('specifications', []))
            ->filter(fn ($row) => is_array($row) && (filled($row['key'] ?? null) || filled($row['value'] ?? null)))
            ->values()
            ->all();

        $this->merge($this->normalizedVariantInput() + [
            'specifications' => $rows,
            // With variants the prices live on the variants; the product's own price fields are ignored.
            'offer_price' => filled($this->input('offer_price')) && ! $this->usesVariants() ? $this->input('offer_price') : null,
            'offer_start_date' => filled($this->input('offer_start_date')) ? $this->input('offer_start_date') : null,
            'offer_end_date' => filled($this->input('offer_end_date')) ? $this->input('offer_end_date') : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->product();
        $maxGallery = (int) config('marketplace.max_gallery_images', 8) - ($product ? $product->images()->count() : 0);

        $variants = $this->usesVariants();

        return $this->variantRules() + [
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->getKey()), Rule::unique('product_variants', 'sku')],

            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'sub_category_id' => ['nullable', 'integer', Rule::exists('sub_categories', 'id')
                ->where('category_id', (int) $this->input('category_id'))
                ->where('is_active', true)->whereNull('deleted_at')],
            'child_category_id' => ['nullable', 'integer', Rule::exists('child_categories', 'id')
                ->where('sub_category_id', (int) $this->input('sub_category_id'))
                ->where('is_active', true)->whereNull('deleted_at')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('is_active', true)->whereNull('deleted_at')],

            'short_description' => ['nullable', 'string', 'max:500'],
            'long_description' => ['nullable', 'string', 'max:65000'],

            'price' => $variants ? ['nullable'] : ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'offer_price' => $variants ? ['nullable'] : ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'lt:price', 'required_with:offer_start_date,offer_end_date'],
            'offer_start_date' => ['nullable', 'date', 'required_with:offer_end_date'],
            'offer_end_date' => ['nullable', 'date', 'required_with:offer_start_date', 'after_or_equal:offer_start_date'],
            'stock_qty' => $variants ? ['nullable'] : ['required', 'integer', 'min:0', 'max:1000000'],

            'video_url' => ['nullable', 'url:http,https', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(ProductStatus::class)],

            'thumbnail' => [$product ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery' => ['nullable', 'array', 'max:'.max(0, $maxGallery)],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gallery_order' => ['nullable', 'array'],
            'gallery_order.*' => ['integer'],

            'specifications' => ['array', 'max:'.(int) config('marketplace.max_specifications', 30)],
            'specifications.*.key' => ['required', 'string', 'max:100'],
            'specifications.*.value' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->variantMessages() + [
            'offer_price.lt' => 'The offer price must be lower than the regular price.',
            'sub_category_id.exists' => 'The sub category must belong to the selected category.',
            'child_category_id.exists' => 'The child category must belong to the selected sub category.',
            'gallery.max' => 'A product can have at most '.config('marketplace.max_gallery_images', 8).' gallery images.',
            'specifications.*.key.required' => 'Each specification needs a name.',
            'specifications.*.value.required' => 'Each specification needs a value.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'sub_category_id' => 'sub category',
            'child_category_id' => 'child category',
            'brand_id' => 'brand',
            'stock_qty' => 'stock quantity',
            'sku' => 'SKU',
            'gallery.*' => 'gallery image',
        ];
    }

    /**
     * Combination, default and SKU checks that need the database or compare rows with each other.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateVariantRows($validator)];
    }

    /**
     * The variant rows as the service expects them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function variantRows(): array
    {
        if (! $this->usesVariants()) {
            return [];
        }

        $rows = [];

        foreach ((array) $this->validated('variants', []) as $key => $row) {
            $rows[] = [
                'id' => $row['id'] !== null ? (int) $row['id'] : null,
                'sku' => $row['sku'],
                'value_ids' => array_map('intval', array_values($row['values'])),
                'price' => (string) $row['price'],
                'offer_price' => $row['offer_price'] === null ? null : (string) $row['offer_price'],
                'stock_qty' => (int) $row['stock_qty'],
                'is_active' => (bool) $row['is_active'],
                'is_default' => (bool) $this->input("variants.$key.is_default"),
                'image' => $this->file("variants.$key.image"),
                'remove_image' => (bool) $this->input("variants.$key.remove_image"),
            ];
        }

        return $rows;
    }

    public function productData(): ProductData
    {
        $attributes = $this->safe()->only([
            'name', 'sku', 'category_id', 'sub_category_id', 'child_category_id', 'brand_id',
            'short_description', 'long_description', 'price', 'offer_price', 'offer_start_date',
            'offer_end_date', 'stock_qty', 'video_url', 'seo_title', 'seo_description', 'status',
        ]);

        // Optional selects that were left empty are cleared.
        foreach (['sub_category_id', 'child_category_id', 'brand_id', 'short_description', 'long_description', 'video_url', 'seo_title', 'seo_description'] as $field) {
            $attributes[$field] = $attributes[$field] ?? null;
        }

        // With variants the product's own price and stock are a mirror of the variants, never posted.
        if ($this->usesVariants()) {
            unset($attributes['price'], $attributes['offer_price'], $attributes['stock_qty']);
        }

        return new ProductData(
            attributes: $attributes,
            specifications: array_map(
                fn (array $row) => ['key' => trim($row['key']), 'value' => trim($row['value'])],
                $this->validated('specifications', []),
            ),
            thumbnail: $this->file('thumbnail'),
            gallery: array_values((array) $this->file('gallery', [])),
            galleryOrder: $this->validated('gallery_order'),
            flags: $this->flags(),
            assignVendor: $this->assignsVendor(),
            vendorId: $this->vendorId(),
            hasVariants: $this->usesVariants(),
            variants: $this->variantRows(),
        );
    }

    /**
     * @return array<string, bool>|null
     */
    protected function flags(): ?array
    {
        return null;
    }

    protected function assignsVendor(): bool
    {
        return false;
    }

    protected function vendorId(): ?int
    {
        return null;
    }
}
