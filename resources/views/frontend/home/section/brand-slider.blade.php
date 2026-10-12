  {{-- $brands (active + featured, ordered) comes from App\View\Composers\BrandSliderComposer. --}}
  @if ($brands->isNotEmpty())
  <section id="wsus__brand_sleder" class="brand_slider_2">
      <div class="container">
          <div class="brand_border">
              <div class="row brand_slider">
                  @foreach ($brands as $brand)
                  <div class="col-xl-2">
                      <div class="wsus__brand_logo">
                          <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="img-fluid w-100" loading="lazy">
                      </div>
                  </div>
                  @endforeach
              </div>
          </div>
      </div>
  </section>
  @endif
