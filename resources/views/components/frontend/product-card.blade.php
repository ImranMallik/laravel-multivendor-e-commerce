{{--
    Storefront product card (template "wsus__product_item"), used on the home page, shop, category,
    vendor shop and related products.
    <x-frontend.product-card :product="$product" />  (load with Product::forCards() to avoid extra queries)
    Ratings, wishlist and quick view arrive with their modules; until then the eye opens the product page.
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
        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid w-100 img_1" width="400" height="400" loading="lazy">
        <img src="{{ $product->hover_image_url }}" alt="{{ $product->name }}" class="img-fluid w-100 img_2" width="400" height="400" loading="lazy">
    </a>
    <ul class="wsus__single_pro_icon">
        <li><a href="{{ route('product.show', $product->slug) }}" aria-label="View {{ $product->name }}"><i class="far fa-eye"></i></a></li>
    </ul>
    <div class="wsus__product_details">
        @if ($product->category)
            <a class="wsus__category" href="{{ route('category.show', $product->category->slug) }}">{{ $product->category->name }}</a>
        @endif
        <a class="wsus__pro_name" href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
        <p class="wsus__price">{{ $product->current_price_label }}
            @if ($product->discount_percent > 0)
                <del>{{ $product->price_label }}</del>
            @endif
        </p>
        @if ($listView && $product->short_description)
            <p class="list_description">{{ $product->short_description }}</p>
        @endif
        @if ($product->in_stock)
            <a class="add_cart" href="{{ route('product.show', $product->slug) }}">view product</a>
        @else
            <span class="add_cart">out of stock</span>
        @endif
    </div>
</div>
