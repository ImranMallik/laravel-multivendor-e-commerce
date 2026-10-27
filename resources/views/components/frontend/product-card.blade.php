{{--
    Storefront product card (template "wsus__product_item"), used on the home page, shop, category,
    vendor shop and related products.
    <x-frontend.product-card :product="$product" />  (load with Product::forCards() to avoid extra queries)

    The template's three icons: the eye opens the quick view (quick-view.js), the heart calls Wishlist.toggle()
    and the shuffle Compare.add() (both placeholders for now). The image and the name link to the product page.
    Ratings arrive with the reviews module.
--}}
@props(['product', 'listView' => false])

<div {{ $attributes->class(['wsus__product_item', 'wsus__list_view' => $listView]) }}>
    @if ($product->is_new)
        <span class="wsus__new">New</span>
    @endif
    @if ($product->discount_percent > 0)
        <span class="wsus__minus">-{{ $product->discount_percent }}%</span>
    @endif
    <a class="wsus__pro_link" href="{{ route('product.show', $product->slug) }}">
        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid w-100 img_1" width="400"
            height="400" loading="lazy">
        <img src="{{ $product->hover_image_url }}" alt="{{ $product->name }}" class="img-fluid w-100 img_2"
            width="400" height="400" loading="lazy">
    </a>
    <ul class="wsus__single_pro_icon">
        <li><a href="{{ route('product.show', $product->slug) }}" role="button" data-quick-view
                data-product-slug="{{ $product->slug }}" title="Quick view"
                aria-label="Quick view: {{ $product->name }}"><i class="far fa-eye"></i></a></li>
        <li><a href="#" role="button" data-wishlist-toggle data-product-id="{{ $product->id }}"
                title="Add to wishlist" aria-label="Add {{ $product->name }} to wishlist"><i
                    class="far fa-heart"></i></a></li>
        <li><a href="#" role="button" data-compare-add data-product-id="{{ $product->id }}" title="Compare"
                aria-label="Compare {{ $product->name }}"><i class="far fa-random"></i></a></li>
    </ul>
    <div class="wsus__product_details">
        @if ($product->category)
            <a class="wsus__category"
                href="{{ route('category.show', $product->category->slug) }}">{{ $product->category->name }}</a>
        @endif
        <a class="wsus__pro_name" href="{{ route('product.show', $product->slug) }}"
            title="{{ $product->name }}">{{ $product->name }}</a>
        <p class="wsus__price">{{ $product->current_price_label }}
            @if ($product->discount_percent > 0)
                <del>{{ $product->price_label }}</del>
            @endif
        </p>
        @if ($listView && $product->short_description)
            <p class="list_description">{{ $product->short_description }}</p>
        @endif
        <x-frontend.cart-button :product="$product" />
    </div>
</div>
