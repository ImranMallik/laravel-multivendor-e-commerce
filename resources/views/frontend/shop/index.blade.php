@extends('frontend.layouts.master')

@section('title', 'Shop')
@section('meta_description', 'Browse all products on '.config('app.name').'.')

@section('content')
    <x-frontend.breadcrumb title="shop" :items="['shop' => null]" />

    <section id="wsus__product_page">
        <div class="container">
            {{-- TODO: the template's sidebar filters (category, price, brand), sorting and list view. --}}
            @include('frontend.shop.partials.grid', ['products' => $products, 'empty' => 'No products yet.'])
        </div>
    </section>
@endsection
