@extends('frontend.layouts.auth')

@section('title', 'Reset Password')
@section('meta_description', 'Choose a new '.config('app.name').' password.')
@section('crumb_title', 'reset password')
@section('crumb', 'reset password')

@section('auth')
    <h1 class="auth-heading">Reset password</h1>
    <p class="auth-subtext">Choose a new password for your account.</p>

    <div class="wsus__login">
        <form method="POST" action="{{ route('password.store') }}" data-auth-form novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-frontend.auth.input name="email" type="email" label="Email" icon="far fa-envelope" autocomplete="email" :value="$request->email" required />
            <x-frontend.auth.input name="password" type="password" label="New password" icon="fas fa-key" autocomplete="new-password" required />
            <x-frontend.auth.input name="password_confirmation" type="password" label="Confirm new password" icon="fas fa-key" autocomplete="new-password" required />

            <button class="common_btn" type="submit">reset password</button>
        </form>
    </div>

    <a class="auth-footer-link" href="{{ route('login') }}">Back to login</a>
@endsection
