 {{-- "Top Categories Of The Month": an "all" tab (Top products) and up to 6 category tabs ($homeProducts from HomeController). --}}
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
         @if ($homeProducts['top_tiles']->isNotEmpty())
             <div class="row">
                 <div class="col-xl-12">
                     <div class="wsus__section_header for_md">
                         <h3>Top Categories Of The Month</h3>
                         {{-- The active button's filter is applied on load by top-categories.js. --}}
                         <div class="monthly_top_filter">
                             @if ($homeProducts['top']->isNotEmpty())
                                 <button class=" active" data-filter=".top-all">all</button>
                             @endif
                             @foreach ($homeProducts['top_categories'] as $category)
                                 <button @class(['active' => $homeProducts['top']->isEmpty() && $loop->first]) data-filter=".top-cat-{{ $category->id }}">{{ $category->name }}</button>
                             @endforeach
                         </div>
                     </div>
                 </div>
             </div>
             <div class="row">
                 <div class="col-xl-12 col-lg-12">
                     <div class="row grid">
                         @foreach ($homeProducts['top_tiles'] as $tile)
                             <div class="col-xl-2 col-6 col-sm-6 col-md-4 col-lg-3 {{ $tile['filters'] }}">
                                 <x-frontend.product-mini :product="$tile['product']" />
                             </div>
                         @endforeach
                     </div>
                 </div>
             </div>
         @endif
     </div>
 </section>
