@extends('admin.layouts.guest')

@section('title', 'Login')

@section('content')
    <x-admin.card title="Admin Login" variant="primary">
        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf

            <x-admin.form.input name="email" type="email" label="Email" autocomplete="email" tabindex="1" required autofocus />
            <x-admin.form.input name="password" type="password" label="Password" autocomplete="current-password" tabindex="2" required />

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                    <label class="custom-control-label" for="remember-me">Remember Me</label>
                </div>
            </div>

            <x-admin.button type="submit" size="lg" block tabindex="4">Login</x-admin.button>

            <div class="mt-3 text-center">
                <a href="{{ route('admin.password.request') }}" class="text-small">Forgot Password?</a>
            </div>
        </form>
    </x-admin.card>
@endsection
