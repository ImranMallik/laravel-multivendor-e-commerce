@extends('frontend.layouts.master')

@section('title', $title)
@section('meta_description', 'Browse '.$title.' on '.config('app.name').'.')

@section('content')
    <x-frontend.breadcrumb :title="$title" :items="$trail" />

    <section id="wsus__product_page">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    @if (count($links))
                        <ul class="list-unstyled d-flex flex-wrap gap-3 mb-4">
                            @foreach ($links as $label => $url)
                                <li><a class="common_btn" href="{{ $url }}">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- TODO: replace with the product listing filtered by this category level. --}}
                    <x-frontend.empty-state message="Products for {{ $title }} are coming soon." link-text="back to home" :link-url="route('home')" />
                </div>
            </div>
        </div>
    </section>
@endsection
