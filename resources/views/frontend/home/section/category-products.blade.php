 {{--
     A category's newest products ("Consumer Electronics", "Apparels & Clothings").
     Which categories appear is set in config('marketplace.home_category_sections').
     Expects: $section (array with category and products), $sectionNumber (1, 2, ...).
 --}}
 <section id="wsus__electronic{{ $sectionNumber > 1 ? $sectionNumber : '' }}">
     <div class="container">
         <div class="row">
             <div class="col-xl-12">
                 <div class="wsus__section_header">
                     <h3>{{ $section['category']->name }}</h3>
                     <a class="see_btn" href="{{ route('category.show', $section['category']->slug) }}">see more <i class="fas fa-caret-right"></i></a>
                 </div>
             </div>
         </div>
         <div class="row flash_sell_slider">
             @foreach ($section['products'] as $product)
                 <div class="col-xl-3 col-sm-6 col-lg-4">
                     <x-frontend.product-card :product="$product" />
                 </div>
             @endforeach
         </div>
     </div>
 </section>
