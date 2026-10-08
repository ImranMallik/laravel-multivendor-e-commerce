@extends('frontend.layouts.auth')

@section('title', 'Login')
@section('meta_description', 'Log in to your '.config('app.name').' account.')
@section('crumb_title', 'login / register')
@section('crumb', 'login')

@section('auth')
    @include('frontend.auth.partials.tabs', ['tab' => 'login'])

    <div class="wsus__login">
        <form method="POST" action="{{ route('login.store') }}" data-auth-form novalidate>
            @csrf

            <x-frontend.auth.input name="email" type="email" label="Email" icon="far fa-envelope" autocomplete="email" required />
            <x-frontend.auth.input name="password" type="password" label="Password" icon="fas fa-key" autocomplete="current-password" required />

            <div class="wsus__login_save">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember-me" @checked(old('remember'))>
                    <label class="form-check-label" for="remember-me">Remember me</label>
                </div>
                <a class="forget_p" href="{{ route('password.request') }}">Forgot password?</a>
            </div>

            <button class="common_btn" type="submit">login</button>
        </form>
    </div>
@endsection
