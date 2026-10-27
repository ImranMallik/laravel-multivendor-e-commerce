 {{--
     Weekly best (template "WEEKLY BEST ITEM"): $weeklyBestRated and $weeklyBestSale from HomeController
     (App\Services\HomeProductService; placeholder rankings until reviews and orders exist, see its TODOs).
     Each .weekly_best2 is its own Slick slider, started once by the template's main.js with its own arrows.
     A list without products hides its column; with both empty the section is not shown.
 --}}
 @if ($weeklyBestRated->isNotEmpty() || $weeklyBestSale->isNotEmpty())
     <section id="wsus__weekly_best" class="home2_wsus__weekly_best_2 ">
         <div class="container">
             <div class="row">
                 @if ($weeklyBestRated->isNotEmpty())
                     <div class="col-xl-6 col-sm-6">
                         <div class="wsus__section_header">
                             <h3>weekly best rated Products</h3>
                         </div>
                         <div class="row weekly_best2" id="weekly-best-rated">
                             @foreach ($weeklyBestRated as $product)
                                 <div class="col-xl-4 col-lg-4">
                                     <x-frontend.product-mini :product="$product" />
                                 </div>
                             @endforeach
                         </div>
                     </div>
                 @endif
                 @if ($weeklyBestSale->isNotEmpty())
                     <div class="col-xl-6 col-sm-6">
                         <div class="wsus__section_header">
                             <h3>weekly best Sale Products</h3>
                         </div>
                         <div class="row weekly_best2" id="weekly-best-sale">
                             @foreach ($weeklyBestSale as $product)
                                 <div class="col-xl-4 col-lg-4">
                                     <x-frontend.product-mini :product="$product" />
                                 </div>
                             @endforeach
                         </div>
                     </div>
                 @endif
             </div>
         </div>
     </section>
 @endif
