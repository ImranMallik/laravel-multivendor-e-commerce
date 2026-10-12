@extends('frontend.layouts.master')
@section('content')
    {{--
        Product sections get $homeProducts from HomeController (App\Repositories\ProductRepository, cached).
        TODO: the template's quick view modal returns with the cart module; product cards link to the product page for now.
    --}}
    @include('frontend.home.section.banner-slider')
    @include('frontend.home.section.flash-sell')

    @include('frontend.home.section.top-categories')

    @include('frontend.home.section.brand-slider')

    @include('frontend.home.section.single-banner')

    @include('frontend.home.section.hot-deals')

    @foreach ($homeProducts['category_sections'] as $section)
        @if ($section['products']->isNotEmpty())
            @include('frontend.home.section.category-products', ['section' => $section, 'sectionNumber' => $loop->iteration])
        @endif
    @endforeach

    @include('frontend.home.section.large-banner')

    @include('frontend.home.section.weekly-best-items')
    @include('frontend.home.section.home-services')
    @include('frontend.home.section.blog')
@endsection
