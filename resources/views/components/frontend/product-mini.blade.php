{{--
    Small product tile (template "wsus__hot_deals__single"): Top Categories, Weekly Best, hot deals strip.
    <x-frontend.product-mini :product="$product" />
    Name, rating and price each keep one line, so every tile has the same height.
--}}
@props(['product'])

<a {{ $attributes->class(['wsus__hot_deals__single', 'w-100']) }} href="{{ route('product.show', $product->slug) }}">
    <div class="wsus__hot_deals__single_img">
        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid w-100" width="200" height="200" loading="lazy">
    </div>
    <div class="wsus__hot_deals__single_text">
        <h5 class="text-truncate" title="{{ $product->name }}">{{ $product->name }}</h5>
        {{-- TODO: real rating after reviews --}}
        <p class="wsus__rating" aria-label="Not rated yet">
            @for ($star = 1; $star <= 5; $star++)
                <i class="far fa-star"></i>
            @endfor
        </p>
        <p class="wsus__tk d-flex align-items-center flex-nowrap">
            <span class="wsus__tk_current">{{ $product->current_price_label }}</span>
            @if ($product->discount_percent > 0)
                <del>{{ $product->price_label }}</del>
            @endif
        </p>
    </div>
</a>
