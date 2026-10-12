@extends('frontend.layouts.master')

@section('title', $title)
@section('meta_description', 'Browse '.$title.' on '.config('app.name').'.')

@section('content')
    <x-frontend.breadcrumb :title="$title" :items="$trail" />

    <section id="wsus__product_page">
        <div class="container">
            @if (count($links))
                <ul class="list-unstyled d-flex flex-wrap gap-3 mb-4">
                    @foreach ($links as $label => $url)
                        <li><a class="common_btn" href="{{ $url }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            @endif

            {{-- TODO: sorting and price / brand filters. --}}
            @include('frontend.shop.partials.grid', ['products' => $products, 'empty' => 'No products in '.$title.' yet.'])
        </div>
    </section>
@endsection
