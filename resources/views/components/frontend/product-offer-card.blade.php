{{--
    Large "hot deal" card (template "wsus__hot_deals_offer") with the product's own offer countdown.
    <x-frontend.product-offer-card :product="$product" />
--}}
@props(['product'])

<div class="wsus__hot_deals_offer">
    <div class="wsus__hot_deals_img">
        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid w-100" width="520" height="550" loading="lazy">
    </div>
    <div class="wsus__hot_deals_text">
        <a class="wsus__hot_title" href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
        <p class="wsus__hot_deals_proce">{{ $product->current_price_label }}
            @if ($product->discount_percent > 0)
                <del>{{ $product->price_label }}</del>
            @endif
        </p>
        @if ($product->short_description)
            <p class="wsus__details">{{ $product->short_description }}</p>
        @endif
        <ul>
            <li><a class="add_cart" href="{{ route('product.show', $product->slug) }}">view product</a></li>
        </ul>
        @if ($product->offer_ends_at)
            <div class="simply-countdown simply-countdown-one" data-countdown-end="{{ $product->offer_ends_at->toIso8601String() }}"></div>
        @endif
    </div>
</div>
