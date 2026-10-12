<?php

namespace App\Http\Requests\Frontend;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Add to cart" from the product page. The product must be visible on the storefront; a product with
 * variants needs one of ITS OWN active variants, a product without variants must not send one.
 */
class AddToCartRequest extends FormRequest
{
    private ?Product $product = null;

    private ?ProductVariant $variant = null;

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
            'product_id' => ['required', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->product = Product::visibleOnStorefront()->with('activeVariants')->find($this->integer('product_id'));

            if (! $this->product) {
                $validator->errors()->add('product_id', 'This product is not available.');

                return;
            }

            if (! $this->product->has_variants) {
                if ($this->filled('product_variant_id')) {
                    $validator->errors()->add('product_variant_id', 'This product has no variants.');
                } elseif (! $this->product->in_stock || $this->integer('quantity') > $this->product->total_stock) {
                    $validator->errors()->add('quantity', 'Not enough stock.');
                }

                return;
            }

            $this->variant = $this->filled('product_variant_id')
                ? $this->product->sellableVariants()->firstWhere('id', $this->integer('product_variant_id'))
                : null;

            if (! $this->variant) {
                $validator->errors()->add('product_variant_id', 'Choose one of the available options.');
            } elseif (! $this->variant->in_stock || $this->integer('quantity') > $this->variant->stock_qty) {
                $validator->errors()->add('quantity', 'Not enough stock for this option.');
            }
        }];
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function variant(): ?ProductVariant
    {
        return $this->variant;
    }
}
