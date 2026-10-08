@extends('dashboard.layouts.app')

@section('title', 'My Profile')
@section('page-title', 'my profile')

@section('content')
    @include('dashboard.partials.profile-forms', [
        'user' => $customer,
        'updateRoute' => route('account.profile.update'),
        'passwordRoute' => route('account.profile.password'),
        'avatarRemoveRoute' => route('account.profile.avatar.destroy'),
    ])
@endsection
