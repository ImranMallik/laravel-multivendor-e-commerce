@extends('admin.layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <x-admin.card title="Forgot Password" variant="primary">
        <p class="text-muted">We will send a link to reset your password.</p>

        <form method="POST" action="{{ route('admin.password.email') }}">
            @csrf

            <x-admin.form.input name="email" type="email" label="Email" autocomplete="email" tabindex="1" required autofocus />

            <x-admin.button type="submit" size="lg" block tabindex="2">Send Reset Link</x-admin.button>
        </form>

        <div class="mt-3 text-center">
            <a href="{{ route('admin.login') }}" class="text-small">Back to login</a>
        </div>
    </x-admin.card>
@endsection
