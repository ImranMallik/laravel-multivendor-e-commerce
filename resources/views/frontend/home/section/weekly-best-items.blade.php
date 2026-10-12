 {{--
     Weekly best ($homeProducts from HomeController).
     Left: "best rated" needs reviews (TODO); until then it shows the newest products.
     Right: products flagged Best (real sales ranking arrives with orders).
 --}}
 @if ($homeProducts['newest']->isNotEmpty() || $homeProducts['best']->isNotEmpty())
 <section id="wsus__weekly_best" class="home2_wsus__weekly_best_2 ">
     <div class="container">
         <div class="row">
             <div class="col-xl-6 col-sm-6">
                 <div class="wsus__section_header">
                     <h3>weekly best rated Products</h3>
                 </div>
                 <div class="row weekly_best2">
                     @foreach ($homeProducts['newest'] as $product)
                         <div class="col-xl-4 col-lg-4">
                             <x-frontend.product-mini :product="$product" />
                         </div>
                     @endforeach
                 </div>
             </div>
             <div class="col-xl-6 col-sm-6">
                 <div class="wsus__section_header">
                     <h3>weekly best Sale Products</h3>
                 </div>
                 <div class="row weekly_best2">
                     @foreach ($homeProducts['best'] as $product)
                         <div class="col-xl-4 col-lg-4">
                             <x-frontend.product-mini :product="$product" />
                         </div>
                     @endforeach
                 </div>
             </div>
         </div>
     </div>
 </section>
 @endif
