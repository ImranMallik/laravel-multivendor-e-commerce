{{--
    Small product tile (template "wsus__hot_deals__single"): Top Categories, Weekly Best, hot deals strip.
    <x-frontend.product-mini :product="$product" />
--}}
@props(['product'])

<a {{ $attributes->class(['wsus__hot_deals__single']) }} href="{{ route('product.show', $product->slug) }}">
    <div class="wsus__hot_deals__single_img">
        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid w-100" width="200" height="200" loading="lazy">
    </div>
    <div class="wsus__hot_deals__single_text">
        <h5>{{ $product->name }}</h5>
        <p class="wsus__tk">{{ $product->current_price_label }}
            @if ($product->discount_percent > 0)
                <del>{{ $product->price_label }}</del>
            @endif
        </p>
    </div>
</a>
