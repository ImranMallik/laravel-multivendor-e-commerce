@extends('frontend.layouts.auth')

@section('title', 'Create Account')
@section('meta_description', 'Create a customer or seller account on '.config('app.name').'.')
@section('crumb_title', 'login / register')
@section('crumb', 'register')

@section('auth')
    @php($vendor = $active === 'vendor')

    @include('frontend.auth.partials.tabs', ['tab' => 'signup'])

    {{-- Secondary switch. Links work without JS; with JS the typed values are kept and only the action changes. --}}
    <p class="auth-switch-label" id="auth-switch-label">Sign up as</p>
    <div class="auth-switch" role="tablist" aria-labelledby="auth-switch-label">
        <a href="{{ route('register') }}" data-auth-mode="customer" role="tab"
           class="{{ $vendor ? '' : 'active' }}" aria-selected="{{ $vendor ? 'false' : 'true' }}">I want to shop</a>
        <a href="{{ route('register.vendor') }}" data-auth-mode="vendor" role="tab"
           class="{{ $vendor ? 'active' : '' }}" aria-selected="{{ $vendor ? 'true' : 'false' }}">I want to sell</a>
    </div>

    <div class="wsus__login">
        <form method="POST" action="{{ $vendor ? route('register.vendor.store') : route('register.store') }}"
              data-auth-form novalidate
              data-customer-action="{{ route('register.store') }}"
              data-vendor-action="{{ route('register.vendor.store') }}">
            @csrf

            <x-frontend.auth.input name="name" label="Full name" icon="fas fa-user-tie" autocomplete="name" required />
            <x-frontend.auth.input name="email" type="email" label="Email" icon="far fa-envelope" autocomplete="email" required />
            <x-frontend.auth.input name="phone" type="tel" label="Phone (optional)" icon="far fa-phone-alt" autocomplete="tel" />

            <div data-vendor-fields class="{{ $vendor ? '' : 'd-none' }}">
                <x-frontend.auth.input name="shop_name" label="Shop name" icon="far fa-store" autocomplete="organization" :required="true" :disabled="! $vendor" />
                <x-frontend.auth.input name="shop_phone" type="tel" label="Shop phone (optional)" icon="far fa-phone-alt" autocomplete="tel" :disabled="! $vendor" />
                <x-frontend.auth.input name="address" label="Shop address" icon="fal fa-map-marker-alt" autocomplete="street-address" :required="true" :disabled="! $vendor" />
            </div>

            <x-frontend.auth.input name="password" type="password" label="Password" icon="fas fa-key" autocomplete="new-password" required />
            <x-frontend.auth.input name="password_confirmation" type="password" label="Confirm password" icon="fas fa-key" autocomplete="new-password" required />

            <button class="common_btn" type="submit"
                    data-text-customer="Create Customer Account" data-text-vendor="Create Seller Account">{{ $vendor ? 'Create Seller Account' : 'Create Customer Account' }}</button>
        </form>
    </div>
@endsection
