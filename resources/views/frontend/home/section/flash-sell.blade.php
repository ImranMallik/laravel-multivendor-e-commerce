 {{-- Products with a dated offer running today, ending soonest first ($homeProducts from HomeController). --}}

 {{-- @dd($homeProducts['flash']) --}}
 @if ($homeProducts['flash']->isNotEmpty())
     <section id="wsus__flash_sell" class="wsus__flash_sell_2">
         <div class=" container">
             <div class="row">
                 <div class="col-xl-12">
                     <div class="offer_time flash_sell_banner">
                         <div class="wsus__flash_coundown">
                             <span class=" end_text">flash sell</span>
                             @if ($homeProducts['flash_ends_at'])
                                 <div class="simply-countdown simply-countdown-one"
                                     data-countdown-end="{{ $homeProducts['flash_ends_at']->toIso8601String() }}"></div>
                             @endif
                             <a class="common_btn" href="{{ route('shop.index') }}">see more <i
                                     class="fas fa-caret-right"></i></a>
                         </div>
                     </div>
                 </div>
             </div>
             <div class="row flash_sell_slider">
                 @foreach ($homeProducts['flash'] as $product)
                     <div class="col-xl-3 col-sm-6 col-lg-4">
                         <x-frontend.product-card :product="$product" />
                     </div>
                 @endforeach
             </div>
         </div>
     </section>
 @endif
