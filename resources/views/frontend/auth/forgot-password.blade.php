@extends('frontend.layouts.auth')

@section('title', 'Forgot Password')
@section('meta_description', 'Reset your '.config('app.name').' password.')
@section('crumb_title', 'forgot password')
@section('crumb', 'forgot password')

@section('auth')
    <h1 class="auth-heading">Forgot password?</h1>
    <p class="auth-subtext">Enter the email you registered with and we will send you a reset link.</p>

    <div class="wsus__login">
        <form method="POST" action="{{ route('password.email') }}" data-auth-form novalidate>
            @csrf

            <x-frontend.auth.input name="email" type="email" label="Email" icon="far fa-envelope" autocomplete="email" required />

            <button class="common_btn" type="submit">send reset link</button>
        </form>
    </div>

    <a class="auth-footer-link" href="{{ route('login') }}">Back to login</a>
@endsection
