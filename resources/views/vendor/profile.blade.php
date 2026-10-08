@extends('dashboard.layouts.app')

@section('title', 'My Profile')
@section('page-title', 'my profile')

@section('content')
    @include('dashboard.partials.profile-forms', [
        'user' => $user,
        'updateRoute' => route('vendor.profile.update'),
        'passwordRoute' => route('vendor.profile.password'),
        'avatarRemoveRoute' => route('vendor.profile.avatar.destroy'),
    ])
@endsection
