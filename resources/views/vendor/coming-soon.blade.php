@extends('dashboard.layouts.app')

@section('title', $section)
@section('page-title', strtolower($section))

@section('content')
    <div class="wsus__dashboard">
        <div class="wsus__message">
            {{-- TODO: build the {{ $section }} module. --}}
            <x-frontend.empty-state :message="$section.' are coming soon.'" link-text="back to dashboard" :link-url="route('vendor.dashboard')" icon="fas fa-tachometer" />
        </div>
    </div>
@endsection
