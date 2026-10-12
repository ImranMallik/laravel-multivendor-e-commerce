@extends('frontend.layouts.master')

@section('title', $product->seo_title ?: $product->name)
@section('meta_description', $product->seo_description ?: ($product->short_description ?: $product->name))

@section('content')
    <x-frontend.breadcrumb :title="$product->name" :items="$breadcrumb" />

    <section id="wsus__product_details">
        <div class="container">
            <div class="wsus__details_bg">
                <div class="row">
                    <div class="col-xl-4 col-md-5 col-lg-5">
                        <div id="sticky_pro_zoom">
                            <div class="exzoom hidden" id="exzoom">
                                <div class="exzoom_img_box">
                                    @if ($product->video_url)
                                        <a class="venobox wsus__pro_det_video" data-autoplay="true" data-vbtype="video" href="{{ $product->video_url }}" aria-label="Play product video">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    @endif
                                    <ul class='exzoom_img_ul'>
                                        <li><img class="zoom ing-fluid w-100" src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}"></li>
                                        @foreach ($product->images as $image)
                                            <li><img class="zoom ing-fluid w-100" src="{{ $image->url }}" alt="{{ $product->name }} image {{ $loop->iteration + 1 }}" loading="lazy"></li>
                                        @endforeach
                                        {{-- Variant images: extra slides; choosing that variant shows its slide (product-variants.js). --}}
                                        @foreach ($product->has_variants ? $product->sellableVariants()->filter->hasImage() : [] as $variant)
                                            <li><img class="zoom ing-fluid w-100" src="{{ $variant->image_url }}" alt="{{ $product->name }} ({{ $variant->label }})" data-variant-slide="{{ $variant->id }}"></li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="exzoom_nav"></div>
                                <p class="exzoom_btn">
                                    <a href="javascript:void(0);" class="exzoom_prev_btn" aria-label="Previous image"> <i class="far fa-chevron-left"></i> </a>
                                    <a href="javascript:void(0);" class="exzoom_next_btn" aria-label="Next image"> <i class="far fa-chevron-right"></i> </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5 col-md-7 col-lg-7">
                        <div class="wsus__pro_details_text">
                            <h1 class="title h4">{{ $product->name }}</h1>
                            {{-- Variant products start on their default variant; product-variants.js switches it in the browser. --}}
                            @php($initial = $product->has_variants ? $product->defaultVariant() : null)
                            @php($display = $initial ?? $product)
                            <p class="wsus__stock_area" data-stock>
                                @if ($display->in_stock)
                                    <span class="in_stock">in stock</span> ({{ $initial ? $initial->stock_qty : $product->total_stock }} item)
                                @else
                                    <span class="in_stock">out of stock</span>
                                @endif
                            </p>
                            <h4 data-price>{{ $initial ? $initial->current_price_label : $product->current_price_label }}
                                @if ($display->discount_percent > 0)
                                    <del>{{ $display->price_label }}</del>
                                @endif
                            </h4>
                            @if ($product->short_description)
                                <p class="description">{{ $product->short_description }}</p>
                            @endif

                            @if ($product->offer_ends_at)
                                <div class="wsus_pro_hot_deals">
                                    <h5>offer ending time : </h5>
                                    <div class="simply-countdown simply-countdown-one" data-countdown-end="{{ $product->offer_ends_at->toIso8601String() }}"></div>
                                </div>
                            @endif

                            @if ($initial)
                                {{-- One group per attribute: swatches for Color, buttons otherwise (template classes). --}}
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
                                <script type="application/json" id="variant-data">@json($product->variantData())</script>
                            @endif

                            <form method="POST" action="{{ route('cart.add') }}" data-add-to-cart>
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                @if ($initial)
                                    <input type="hidden" name="product_variant_id" value="{{ $initial->id }}" data-variant-input>
                                @endif
                                <div class="wsus__quentity">
                                    <h5>quantity :</h5>
                                    <input class="number_area" type="number" name="quantity" min="1" value="1" aria-label="Quantity">
                                </div>
                                <ul class="wsus__button_area">
                                    {{-- TODO: the cart itself arrives with the cart module; for now the selected variant id is validated and echoed back. --}}
                                    <li><button type="submit" class="add_cart" data-add-button @disabled(! $display->in_stock)>add to cart</button></li>
                                </ul>
                            </form>

                            <p class="brand_model"><span>model :</span> <span data-sku>{{ $initial?->sku ?? $product->sku }}</span></p>
                            @if ($product->brand)
                                <p class="brand_model"><span>brand :</span> {{ $product->brand->name }}</p>
                            @endif
                            <p class="brand_model"><span>category :</span>
                                <a href="{{ route('category.show', $product->category->slug) }}">{{ $product->categoryTrail() }}</a>
                            </p>
                            @if ($product->vendor)
                                <p class="brand_model"><span>sold by :</span>
                                    <a href="{{ route('store.show', $product->vendor->slug) }}">{{ $product->vendor->shop_name }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-12 mt-md-5 mt-lg-0">
                        <div class="wsus_pro_det_sidebar" id="sticky_sidebar">
                            {{-- Store-wide promises from the template (not stored per product). --}}
                            <ul>
                                <li>
                                    <span><i class="fal fa-truck"></i></span>
                                    <div class="text"><h4>Return Available</h4></div>
                                </li>
                                <li>
                                    <span><i class="far fa-shield-check"></i></span>
                                    <div class="text"><h4>Secure Payment</h4></div>
                                </li>
                                <li>
                                    <span><i class="fal fa-envelope-open-dollar"></i></span>
                                    <div class="text"><h4>Warranty Available</h4></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="wsus__pro_det_description">
                        <div class="wsus__details_bg">
                            <ul class="nav nav-pills mb-3" id="product-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab-description" data-bs-toggle="pill" data-bs-target="#pane-description"
                                            type="button" role="tab" aria-controls="pane-description" aria-selected="true">Description</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab-information" data-bs-toggle="pill" data-bs-target="#pane-information"
                                            type="button" role="tab" aria-controls="pane-information" aria-selected="false">Information</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab-vendor" data-bs-toggle="pill" data-bs-target="#pane-vendor"
                                            type="button" role="tab" aria-controls="pane-vendor" aria-selected="false">Vendor Info</button>
                                </li>
                                {{-- TODO: Reviews tab with the reviews module. --}}
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="pane-description" role="tabpanel" aria-labelledby="tab-description">
                                    <div class="wsus__description_area">
                                        {{-- Sanitized when saved (App\Services\HtmlSanitizer). --}}
                                        @if ($product->long_description)
                                            {!! $product->long_description !!}
                                        @else
                                            <p>{{ $product->short_description ?: 'No description yet.' }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pane-information" role="tabpanel" aria-labelledby="tab-information">
                                    <div class="row">
                                        @forelse ($specificationColumns as $column)
                                            <div class="col-xl-6 col-lg-6 mb-4 mb-lg-0">
                                                <div class="wsus__pro_det_info">
                                                    <h4>Additional Information</h4>
                                                    @foreach ($column as $spec)
                                                        <p><span>{{ $spec->key }}</span> {{ $spec->value }}</p>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12"><p>No additional information.</p></div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pane-vendor" role="tabpanel" aria-labelledby="tab-vendor">
                                    <div class="wsus__pro_det_vendor">
                                        @if ($product->vendor)
                                            <div class="row">
                                                <div class="col-xl-6 col-xxl-5 col-md-6">
                                                    <div class="wsus__vebdor_img">
                                                        <img src="{{ $product->vendor->logo_url }}" alt="{{ $product->vendor->shop_name }}" class="img-fluid w-100" loading="lazy">
                                                    </div>
                                                </div>
                                                <div class="col-xl-6 col-xxl-7 col-md-6 mt-4 mt-md-0">
                                                    <div class="wsus__pro_det_vendor_text">
                                                        <h4>{{ $product->vendor->shop_name }}</h4>
                                                        <p><span>Store Name:</span> {{ $product->vendor->shop_name }}</p>
                                                        <p><span>Address:</span> {{ $product->vendor->address }}</p>
                                                        @if ($product->vendor->phone)<p><span>Phone:</span> {{ $product->vendor->phone }}</p>@endif
                                                        <a href="{{ route('store.show', $product->vendor->slug) }}" class="see_btn">visit store</a>
                                                    </div>
                                                </div>
                                                @if ($product->vendor->description)
                                                    <div class="col-xl-12">
                                                        <div class="wsus__vendor_details"><p>{{ $product->vendor->description }}</p></div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="wsus__pro_det_vendor_text">
                                                <h4>{{ config('app.name') }}</h4>
                                                <p>Sold and shipped by {{ config('app.name') }}.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section id="wsus__flash_sell">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="wsus__section_header">
                            <h3>Related Products</h3>
                            <a class="see_btn" href="{{ route('category.show', $product->category->slug) }}">see more <i class="fas fa-caret-right"></i></a>
                        </div>
                    </div>
                </div>
                <div class="row flash_sell_slider">
                    @foreach ($related as $item)
                        <div class="col-xl-3 col-sm-6 col-lg-4">
                            <x-frontend.product-card :product="$item" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@if ($product->has_variants)
    @push('scripts')
        <script src="{{ asset('frontend-assets/js/product-variants.js') }}"></script>
    @endpush
@endif
