@extends('frontend.layouts.master')

@section('title', $vendor->shop_name)
@section('meta_description', 'Products from '.$vendor->shop_name.' on '.config('app.name').'.')

@section('content')
    <x-frontend.breadcrumb :title="$vendor->shop_name" :items="['vendors' => null, $vendor->shop_name => null]" />

    <section id="wsus__product_page">
        <div class="container">
            <div class="wsus__pro_det_vendor mb-4">
                <div class="row align-items-center">
                    <div class="col-md-2 col-4">
                        <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->shop_name }}" class="img-fluid rounded" width="160" height="160">
                    </div>
                    <div class="col-md-10 col-8">
                        <div class="wsus__pro_det_vendor_text">
                            <h4>{{ $vendor->shop_name }}</h4>
                            <p><span>Address:</span> {{ $vendor->address }}</p>
                            @if ($vendor->phone)<p><span>Phone:</span> {{ $vendor->phone }}</p>@endif
                            @if ($vendor->description)<p>{{ $vendor->description }}</p>@endif
                        </div>
                    </div>
                </div>
            </div>

            @include('frontend.shop.partials.grid', ['products' => $products, 'empty' => $vendor->shop_name.' has no products yet.'])
        </div>
    </section>
@endsection
