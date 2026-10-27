{{--
    Quick view modal BODY of one product (template index.html "product_popup_modal" markup), returned as HTML
    inside JSON by QuickViewController. Expects $product as loaded by ProductRepository::findVisibleBySlug()
    (images, brand, category, variants with their values and attributes).

    Behaviour (all through data-* hooks): quick-view.js (gallery slider, video popup, quantity buttons,
    countdown), product-variants.js (options, price, stock, SKU, image, live total), cart.js / wishlist.js /
    compare.js (buttons). Prices come from the product's price accessors, the same as the cards and the page.
--}}
@php
    // Variant products open on their default variant; product-variants.js switches it in the browser.
    $initial = $product->has_variants ? $product->defaultVariant() : null;
    $display = $initial ?? $product;
    $stock = $initial ? $initial->stock_qty : $product->total_stock;
    $productUrl = route('product.show', $product->slug);
@endphp

<div class="row" data-product-root data-gallery="slick"
     data-product-id="{{ $product->id }}"
     data-currency="{{ config('marketplace.currency_symbol') }}"
     data-unit-price="{{ (float) $display->current_price }}"
     data-stock-qty="{{ $stock }}">
    <div class="col-xl-6 col-12 col-sm-10 col-md-8 col-lg-6 m-auto display">
        <div class="wsus__quick_view_img">
            @if ($product->video_url)
                <a class="venobox wsus__pro_det_video" data-autoplay="true" data-vbtype="video"
                   href="{{ $product->video_url }}" aria-label="Play the product video">
                    <i class="fas fa-play"></i>
                </a>
            @endif
            <div class="row modal_slider">
                <div class="col-xl-12">
                    <div class="modal_slider_img">
                        <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="img-fluid w-100">
                    </div>
                </div>
                @foreach ($product->images as $image)
                    <div class="col-xl-12">
                        <div class="modal_slider_img">
                            <img src="{{ $image->url }}" alt="{{ $product->name }}" class="img-fluid w-100" loading="lazy">
                        </div>
                    </div>
                @endforeach
                @foreach ($initial ? $product->sellableVariants()->filter->hasImage() : [] as $variant)
                    <div class="col-xl-12">
                        <div class="modal_slider_img">
                            <img src="{{ $variant->image_url }}" alt="{{ $product->name }} ({{ $variant->label }})" class="img-fluid w-100"
                                 loading="lazy" data-variant-slide="{{ $variant->id }}">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-6 col-12 col-sm-12 col-md-12 col-lg-6">
        <div class="wsus__pro_details_text">
            <a class="title" href="{{ $productUrl }}">{{ $product->name }}</a>
            <p class="wsus__stock_area" data-stock>
                @if ($display->in_stock)
                    <span class="in_stock">in stock</span> ({{ $stock }} item)
                @else
                    <span class="in_stock">out of stock</span>
                @endif
            </p>
            <h4 data-price>{{ $initial ? $initial->current_price_label : $product->current_price_label }}
                @if ($display->discount_percent > 0)
                    <del>{{ $display->price_label }}</del>
                @endif
            </h4>
            {{-- TODO(reviews): real rating and review count when reviews exist. --}}
            <p class="review" aria-label="Not rated yet">
                @for ($star = 1; $star <= 5; $star++)
                    <i class="far fa-star"></i>
                @endfor
                <span>0 review</span>
            </p>
            @if ($product->short_description)
                <p class="description">{{ $product->short_description }}</p>
            @endif

            @if ($product->offer_ends_at)
                <div class="wsus_pro_hot_deals">
                    <h5>offer ending time : </h5>
                    <div class="simply-countdown simply-countdown-one"
                         data-countdown-end="{{ $product->offer_ends_at->toIso8601String() }}"
                         data-server-now="{{ now()->getTimestampMs() }}"></div>
                </div>
            @endif

            @if ($initial)
                {{-- One row per attribute: Color as the template's swatches, every other attribute as its size buttons. --}}
                @foreach ($product->usedAttributes() as $group)
                    @php($attribute = $group['attribute'])
                    <div class="{{ $attribute->isColor() ? 'wsus_pro_det_color' : 'wsus_pro__det_size' }}" data-variant-attribute="{{ $attribute->id }}">
                        <h5>{{ strtolower($attribute->name) }} :</h5>
                        <ul>
                            @foreach ($group['values'] as $value)
                                <li>
                                    <a href="#" role="button" title="{{ $value->value }}" aria-label="{{ $attribute->name }}: {{ $value->value }}"
                                       class="variant-option {{ $initial->values->contains('id', $value->id) ? 'is-selected' : '' }}"
                                       data-attribute-id="{{ $attribute->id }}" data-value-id="{{ $value->id }}"
                                       @if ($attribute->isColor()) data-color="{{ $value->color_code }}" @endif>
                                        @if ($attribute->isColor())<i class="far fa-check"></i>@else{{ $value->value }}@endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                <script type="application/json" data-variant-data>@json($product->variantData())</script>
                <input type="hidden" value="{{ $initial->id }}" data-variant-input>
            @endif

            <div class="wsus__quentity">
                <h5>quantity :</h5>
                <form class="select_number" data-quantity-form>
                    <input class="number_area" type="text" inputmode="numeric" min="1" max="{{ max(1, $stock) }}" value="1"
                           aria-label="Quantity" data-quantity>
                </form>
                <h3 data-total>{{ $initial ? $initial->current_price_label : $product->current_price_label }}</h3>
            </div>

            {{-- The template's two "select" dropdowns are left out: every product attribute is shown above. --}}

            <ul class="wsus__button_area">
                <li>
                    {{-- TODO(cart): Cart.add() only validates for now; the cart itself comes later. --}}
                    <a class="add_cart" href="#" role="button" data-cart-add data-add-button
                       @unless ($display->in_stock) aria-disabled="true"@endunless>{{ $display->in_stock ? 'add to cart' : 'out of stock' }}</a>
                </li>
                <li><a class="buy_now" href="{{ $productUrl }}">view full details</a></li>
                <li><a href="#" role="button" data-wishlist-toggle data-product-id="{{ $product->id }}" aria-label="Add to wishlist"><i class="fal fa-heart"></i></a></li>
                <li><a href="#" role="button" data-compare-add data-product-id="{{ $product->id }}" aria-label="Add to compare"><i class="far fa-random"></i></a></li>
            </ul>
            <p class="brand_model"><span>model :</span> <span data-sku>{{ $initial?->sku ?? $product->sku }}</span></p>
            @if ($product->brand)
                <p class="brand_model"><span>brand :</span> {{ $product->brand->name }}</p>
            @endif
            <div class="wsus__pro_det_share">
                <h5>share :</h5>
                <ul class="d-flex">
                    <li><a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($productUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a></li>
                    <li><a class="twitter" href="https://twitter.com/intent/tweet?url={{ urlencode($productUrl) }}&amp;text={{ urlencode($product->name) }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Twitter"><i class="fab fa-twitter"></i></a></li>
                    <li><a class="whatsapp" href="https://wa.me/?text={{ urlencode($product->name.' '.$productUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
