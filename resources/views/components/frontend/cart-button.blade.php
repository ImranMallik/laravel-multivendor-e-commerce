{{--
    The template's "add to cart" link (a.add_cart) on product cards. Handled by public/frontend-assets/js/cart.js:
      - out of stock: disabled "out of stock"
      - product with variants: opens the quick view (the options must be chosen first)
      - otherwise: Cart.add(productId) — TODO(cart): a placeholder that only validates for now
    <x-frontend.cart-button :product="$product" />
--}}
@props(['product'])

@if (! $product->in_stock)
    <a {{ $attributes->class(['add_cart']) }} role="button" aria-disabled="true" tabindex="-1">out of stock</a>
@elseif ($product->has_variants)
    <a {{ $attributes->class(['add_cart']) }} href="{{ route('product.show', $product->slug) }}" role="button"
       data-quick-view data-product-slug="{{ $product->slug }}">add to cart</a>
@else
    <a {{ $attributes->class(['add_cart']) }} href="#" role="button" data-cart-add data-product-id="{{ $product->id }}">add to cart</a>
@endif
