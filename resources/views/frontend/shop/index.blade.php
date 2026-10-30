{{--
    Shop page and search results (?search=&sort=&category=&brand=). Expects from ShopController: $products
    (paginator), $term, $searching, $category / $brand (filters or null), $sort, $sortOptions, $keep, $popularCategories.
--}}
@extends('frontend.layouts.master')

@section('title', $searching ? 'Search: '.$term.' | '.config('app.name') : 'Shop')
@section('meta_description', $searching ? 'Search results for '.$term.' on '.config('app.name').'.' : 'Browse all products on '.config('app.name').'.')

@if ($searching)
    {{-- Search result pages are not indexed. --}}
    @push('meta')
        <meta name="robots" content="noindex, follow">
    @endpush
@endif

@section('content')
    <x-frontend.breadcrumb :title="$searching ? 'search' : 'shop'"
                           :items="$searching ? ['shop' => route('shop.index'), 'search' => null] : ['shop' => null]" />

    <section id="wsus__product_page">
        <div class="container">
            @if ($searching)
                <x-frontend.section-title :title="'Search results for “'.$term.'” ('.$products->total().' '.str('product')->plural($products->total()).')'" />
            @elseif ($category || $brand)
                <x-frontend.section-title :title="collect([$category?->name, $brand?->name])->filter()->implode(' · ').' ('.$products->total().' '.str('product')->plural($products->total()).')'" />
            @endif

            @if ($products->isNotEmpty())
                @include('frontend.shop.partials.sort-bar')
            @endif

            {{-- TODO: the template's sidebar filters (category accordion, price slider, brand checkboxes) and the list view;
                 they will combine with the search through the same query string. --}}
            @if ($searching && $products->isEmpty())
                @include('frontend.shop.partials.search-empty')
            @else
                @include('frontend.shop.partials.grid', ['products' => $products, 'empty' => 'No products yet.'])
            @endif
        </div>
    </section>
@endsection
