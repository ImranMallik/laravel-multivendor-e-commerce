{{--
    Home banner. Slides come from the admin Sliders section ($sliders is passed by HomeController,
    cached and cleared by SliderObserver). The template's markup and classes are unchanged;
    the slide image is a CSS background, exactly as in the template. Hidden when nothing is active.
--}}
@if (isset($sliders) && $sliders->isNotEmpty())
    <section id="wsus__banner">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="wsus__banner_content">
                        <div class="row banner_slider">
                            @foreach ($sliders as $slider)
                                <div class="col-xl-12">
                                    <div class="wsus__single_slider" style="background: url('{{ $slider->image_url }}');">
                                        <div class="wsus__single_slider_text">
                                            @if (filled($slider->top_text))
                                                <h3>{{ $slider->top_text }}</h3>
                                            @endif
                                            <h1>{{ $slider->title }}</h1>
                                            @if (filled($slider->offer_text))
                                                <h6>{{ $slider->offer_text }}</h6>
                                            @endif
                                            @if ($slider->hasButton())
                                                <a class="common_btn" href="{{ $slider->button_url }}">{{ $slider->button_text }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
