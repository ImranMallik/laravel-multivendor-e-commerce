 {{--
     "Hot deals of the day": featured products ($homeProducts from HomeController).
     The large cards are featured products with a running offer (each with its own countdown),
     the grid lists all featured products with category tabs, and the strip shows the newest products.
 --}}
 @if ($homeProducts['featured']->isNotEmpty())
 <section id="wsus__hot_deals" class="wsus__hot_deals_2">
     <div class="container">
         <div class="row">
             <div class="col-xl-12">
                 <div class="wsus__section_header">
                     <h3>hot deals of the day</h3>
                 </div>
             </div>
         </div>
         @if ($homeProducts['hot_offers']->isNotEmpty())
             <div class="row hot_deals_slider_2">
                 @foreach ($homeProducts['hot_offers'] as $product)
                     <div class="col-xl-4 col-lg-6">
                         <x-frontend.product-offer-card :product="$product" />
                     </div>
                 @endforeach
             </div>
         @endif
         <div class="wsus__hot_large_item">
             <div class="row">
                 <div class="col-xl-12">
                     <div class="wsus__section_header justify-content-start">
                         <div class="monthly_top_filter2 mb-1">
                             <button class="ms-0 active" data-filter="*">all</button>
                             @foreach ($homeProducts['featured_categories'] as $category)
                                 <button data-filter=".hot-cat-{{ $category->id }}">{{ $category->name }}</button>
                             @endforeach
                         </div>
                     </div>
                 </div>
             </div>
             <div class="row grid2">
                 @foreach ($homeProducts['featured'] as $product)
                     <div class="col-xl-3 col-sm-6 col-md-4 col-lg-4 hot-cat-{{ $product->category_id }}">
                         <x-frontend.product-card :product="$product" />
                     </div>
                 @endforeach
             </div>
         </div>

         <section id="wsus__single_banner" class="home_2_single_banner">
             <div class="container">
                 <div class="row">
                     <div class="col-xl-6 col-lg-6">
                         <div class="wsus__single_banner_content banner_1">
                             <div class="wsus__single_banner_img">
                                 <img src="{{ asset('frontend-assets/images/single_banner_44.jpg') }}" alt="banner" class="img-fluid w-100" loading="lazy">
                             </div>
                             <div class="wsus__single_banner_text">
                                 <h6>sell on <span>35% off</span></h6>
                                 <h3>smart watch</h3>
                                 <a class="shop_btn" href="{{ route('shop.index') }}">shop now</a>
                             </div>
                         </div>
                     </div>
                     <div class="col-xl-6 col-lg-6">
                         <div class="row">
                             <div class="col-12">
                                 <div class="wsus__single_banner_content single_banner_2">
                                     <div class="wsus__single_banner_img">
                                         <img src="{{ asset('frontend-assets/images/single_banner_55.jpg') }}" alt="banner"
                                             class="img-fluid w-100" loading="lazy">
                                     </div>
                                     <div class="wsus__single_banner_text">
                                         <h6>New Collection</h6>
                                         <h3>kid's fashion</h3>
                                         <a class="shop_btn" href="{{ route('shop.index') }}">shop now</a>
                                     </div>
                                 </div>
                             </div>
                             <div class="col-12 mt-lg-4">
                                 <div class="wsus__single_banner_content">
                                     <div class="wsus__single_banner_img">
                                         <img src="{{ asset('frontend-assets/images/single_banner_66.jpg') }}" alt="banner"
                                             class="img-fluid w-100" loading="lazy">
                                     </div>
                                     <div class="wsus__single_banner_text">
                                         <h6>sell on <span>42% off</span></h6>
                                         <h3>winter collection</h3>
                                         <a class="shop_btn" href="{{ route('shop.index') }}">shop now</a>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </section>

         @if ($homeProducts['newest']->isNotEmpty())
             <div class="wsus__hot_small_item wsus__hot_small_item_2">
                 <div class="row">
                     @foreach ($homeProducts['newest']->take(6) as $product)
                         <div class="col-xl-2 col-6 col-sm-6 col-md-4 col-lg-3">
                             <x-frontend.product-mini :product="$product" />
                         </div>
                     @endforeach
                 </div>
             </div>
         @endif
     </div>
 </section>
 @endif
