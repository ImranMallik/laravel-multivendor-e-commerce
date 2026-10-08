@extends('admin.layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <x-admin.card title="Reset Password" variant="primary">
        <form method="POST" action="{{ route('admin.password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-admin.form.input name="email" type="email" label="Email" :value="$request->email" autocomplete="email" tabindex="1" required autofocus />
            <x-admin.form.input name="password" type="password" label="New Password" autocomplete="new-password" tabindex="2" required />
            <x-admin.form.input name="password_confirmation" type="password" label="Confirm Password" autocomplete="new-password" tabindex="3" required />

            <x-admin.button type="submit" size="lg" block tabindex="4">Reset Password</x-admin.button>
        </form>
    </x-admin.card>
@endsection
