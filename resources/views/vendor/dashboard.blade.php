@extends('dashboard.layouts.app')

@section('title', 'Vendor Dashboard')
@section('page-title', 'dashboard')

@section('content')
    <div class="wsus__dashboard">
        {{-- TODO: replace the 0 placeholders with real counts once products, orders and earnings exist. --}}
        <div class="row">
            <div class="col-xl-3 col-6 col-md-4">
                <a class="wsus__dashboard_item red" href="{{ route('vendor.products') }}">
                    <i class="far fa-box"></i>
                    <p>products</p>
                    <h4>0</h4>
                </a>
            </div>
            <div class="col-xl-3 col-6 col-md-4">
                <a class="wsus__dashboard_item blue" href="{{ route('vendor.orders') }}">
                    <i class="far fa-address-book"></i>
                    <p>orders</p>
                    <h4>0</h4>
                </a>
            </div>
            <div class="col-xl-3 col-6 col-md-4">
                <a class="wsus__dashboard_item green" href="{{ route('vendor.earnings') }}">
                    <i class="far fa-dollar-sign"></i>
                    <p>earnings</p>
                    <h4>$0.00</h4>
                </a>
            </div>
            <div class="col-xl-3 col-6 col-md-4">
                <a class="wsus__dashboard_item orange" href="{{ route('vendor.shop.edit') }}">
                    <i class="far fa-store"></i>
                    <p>shop settings</p>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="wsus__message">
                    <h4>welcome, {{ auth('web')->user()->name }}</h4>
                    <p>{{ $vendor->shop_name }} is live. Products, orders and earnings will appear here once those modules are added.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
