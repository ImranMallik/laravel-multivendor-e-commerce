 {{-- "Top Categories Of The Month": products flagged Top ($homeProducts from HomeController); the tabs filter them by category. --}}
 <section id="wsus__monthly_top" class="wsus__monthly_top_2">
     <div class="container">
         <div class="row">
             <div class="col-xl-12 col-lg-12">
                 <div class="wsus__monthly_top_banner">
                     <div class="wsus__monthly_top_banner_img">
                         <img src="{{ asset('frontend-assets/images/monthly_top_img3.jpg') }}" alt="Black Friday sale" class="img-fluid w-100" width="1300" height="300" loading="lazy">
                         <span></span>
                     </div>
                     <div class="wsus__monthly_top_banner_text">
                         <h4>Black Friday Sale</h4>
                         <h3>Up To <span>70% Off</span></h3>
                         <H6>Everything</H6>
                         <a class="shop_btn" href="{{ route('shop.index') }}">shop now</a>
                     </div>
                 </div>
             </div>
         </div>
         @if ($homeProducts['top']->isNotEmpty())
             <div class="row">
                 <div class="col-xl-12">
                     <div class="wsus__section_header for_md">
                         <h3>Top Categories Of The Month</h3>
                         <div class="monthly_top_filter">
                             <button class=" active" data-filter="*">all</button>
                             @foreach ($homeProducts['top_categories'] as $category)
                                 <button data-filter=".top-cat-{{ $category->id }}">{{ $category->name }}</button>
                             @endforeach
                         </div>
                     </div>
                 </div>
             </div>
             <div class="row">
                 <div class="col-xl-12 col-lg-12">
                     <div class="row grid">
                         @foreach ($homeProducts['top'] as $product)
                             <div class="col-xl-2 col-6 col-sm-6 col-md-4 col-lg-3 top-cat-{{ $product->category_id }}">
                                 <x-frontend.product-mini :product="$product" />
                             </div>
                         @endforeach
                     </div>
                 </div>
             </div>
         @endif
     </div>
 </section>
