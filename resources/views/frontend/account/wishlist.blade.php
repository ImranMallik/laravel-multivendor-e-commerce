@extends('dashboard.layouts.app')

@section('title', 'My Wishlist')
@section('page-title', 'my wishlist')

@section('content')
    <div class="wsus__dashboard">
        <div class="wsus__message">
            {{-- TODO: replace with real data (Wishlist items) --}}
            <x-frontend.empty-state message="Your wishlist is empty." link-text="browse products" :link-url="route('home')" icon="far fa-heart" />
        </div>
    </div>
@endsection
