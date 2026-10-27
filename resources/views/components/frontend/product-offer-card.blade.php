{{--
    Large "hot deal" card (template "wsus__hot_deals_offer") with the product's own offer countdown and the
    template's add to cart / wishlist / compare row (cart.js, wishlist.js, compare.js).
    <x-frontend.product-offer-card :product="$product" />
--}}
@props(['product'])

<div class="wsus__hot_deals_offer">
    <div class="wsus__hot_deals_img">
        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid w-100" width="520" height="550" loading="lazy">
    </div>
    <div class="wsus__hot_deals_text">
        <a class="wsus__hot_title" href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
        {{-- TODO(reviews): real rating and review count when reviews exist. --}}
        <p class="wsus__rating" aria-label="Not rated yet">
            @for ($star = 1; $star <= 5; $star++)
                <i class="far fa-star"></i>
            @endfor
            <span>(0 review)</span>
        </p>
        <p class="wsus__hot_deals_proce">{{ $product->current_price_label }}
            @if ($product->discount_percent > 0)
                <del>{{ $product->price_label }}</del>
            @endif
        </p>
        @if ($product->short_description)
            <p class="wsus__details">{{ $product->short_description }}</p>
        @endif
        <ul>
            <li><x-frontend.cart-button :product="$product" /></li>
            <li><a href="#" role="button" data-wishlist-toggle data-product-id="{{ $product->id }}"
                   aria-label="Add {{ $product->name }} to wishlist"><i class="far fa-heart"></i></a></li>
            <li><a href="#" role="button" data-compare-add data-product-id="{{ $product->id }}"
                   aria-label="Compare {{ $product->name }}"><i class="far fa-random"></i></a></li>
        </ul>
        @if ($product->offer_ends_at)
            <div class="simply-countdown simply-countdown-one" data-countdown-end="{{ $product->offer_ends_at->toIso8601String() }}"
                 data-server-now="{{ now()->getTimestampMs() }}"></div>
        @endif
    </div>
</div>
