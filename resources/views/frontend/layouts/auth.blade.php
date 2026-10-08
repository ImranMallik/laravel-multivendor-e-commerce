{{--
    Shared shell for every auth page (login, signup, forgot/reset password):
    one centered column and one card, so all of them have identical width and padding.
    Child views fill: title, crumb_title, crumb and the "auth" section.
--}}
@extends('frontend.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend-assets/css/auth.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('frontend-assets/js/auth.js') }}"></script>
@endpush

@section('inline-errors', 'yes')

@section('content')
    <x-frontend.breadcrumb :title="trim($__env->yieldContent('crumb_title', 'login / register'))" :items="[trim($__env->yieldContent('crumb', 'login')) => null]" />

    <section id="wsus__login_register">
        <div class="container">
            <div class="row">
                <div class="col-xl-5 col-lg-6 col-md-8 m-auto">
                    <div class="wsus__login_reg_area">
                        @yield('auth')
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
