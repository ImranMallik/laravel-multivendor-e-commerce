@extends('dashboard.layouts.app')

@section('title', 'My Orders')
@section('page-title', 'my orders')

@section('content')
    <div class="wsus__dashboard">
        <div class="wsus__message">
            {{-- TODO: replace with real data (Order model list) --}}
            <x-frontend.empty-state message="You have not placed any orders yet." link-text="start shopping" :link-url="route('home')" />
        </div>
    </div>
@endsection
