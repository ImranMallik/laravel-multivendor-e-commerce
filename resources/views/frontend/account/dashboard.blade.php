@extends('dashboard.layouts.app')

@section('title', 'My Account')
@section('page-title', 'my account')

@section('content')
    <div class="wsus__dashboard">
        <div class="row">
            @foreach ([
                ['account.orders', 'red', 'far fa-address-book', 'orders'],
                ['account.wishlist', 'blue', 'far fa-heart', 'wishlist'],
                ['account.profile.edit', 'orange', 'fas fa-user-shield', 'profile'],
            ] as [$routeName, $color, $icon, $label])
                <div class="col-xl-2 col-6 col-md-4">
                    <a class="wsus__dashboard_item {{ $color }}" href="{{ route($routeName) }}">
                        <i class="{{ $icon }}"></i>
                        <p>{{ $label }}</p>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="wsus__message">
                    <h4>welcome back, {{ $customer->name }}</h4>
                    <p>Use the menu to view your orders, manage your wishlist and update your profile.</p>
                </div>
                <div class="wsus__message">
                    <h4>personal information</h4>
                    <div class="row">
                        <div class="col-xl-6"><div class="wsus__single_inout"><label>name</label><p>{{ $customer->name }}</p></div></div>
                        <div class="col-xl-6"><div class="wsus__single_inout"><label>email</label><p>{{ $customer->email }}</p></div></div>
                        <div class="col-xl-6"><div class="wsus__single_inout"><label>phone</label><p>{{ $customer->phone ?: '—' }}</p></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
