@props(['product'])

<div class="wsus__product_item">
    @if ($product['badge'])
        <span class="wsus__new">{{ $product['badge'] }}</span>
    @endif
    @if ($product['discount'])
        <span class="wsus__minus">{{ $product['discount'] }}</span>
    @endif
    <a class="wsus__pro_link" href="{{ route('product.show', $product['slug']) }}">
        <img src="{{ asset('frontend-assets/images/'.$product['image']) }}" alt="{{ $product['name'] }}" class="img-fluid w-100 img_1">
        <img src="{{ asset('frontend-assets/images/'.$product['image_hover']) }}" alt="{{ $product['name'] }}" class="img-fluid w-100 img_2">
    </a>
    <ul class="wsus__single_pro_icon">
        <li><a href="{{ route('account.wishlist') }}"><i class="far fa-heart"></i></a></li>
    </ul>
    <div class="wsus__product_details">
        <a class="wsus__category" href="{{ route('category.show', $product['category']) }}">{{ $product['category'] }}</a>
        <p class="wsus__pro_rating">
            @for ($i = 1; $i <= 5; $i++)
                @if ($product['rating'] >= $i)
                    <i class="fas fa-star"></i>
                @elseif ($product['rating'] >= $i - 0.5)
                    <i class="fas fa-star-half-alt"></i>
                @else
                    <i class="far fa-star"></i>
                @endif
            @endfor
            <span>({{ $product['reviews'] }} review)</span>
        </p>
        <a class="wsus__pro_name" href="{{ route('product.show', $product['slug']) }}">{{ $product['name'] }}</a>
        <p class="wsus__price">${{ number_format($product['price'], 2) }}
            @if ($product['old_price'])
                <del>${{ number_format($product['old_price'], 2) }}</del>
            @endif
        </p>
        <a class="add_cart" href="{{ route('product.show', $product['slug']) }}">view product</a>
    </div>
</div>
