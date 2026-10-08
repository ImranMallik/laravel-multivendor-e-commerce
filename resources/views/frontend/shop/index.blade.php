@extends('frontend.layouts.master')

@section('title', 'Shop')
@section('meta_description', 'Browse all categories on '.config('app.name').'.')

@section('content')
    <x-frontend.breadcrumb title="shop" :items="['shop' => null]" />

    <section id="wsus__product_page">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    @forelse ($categories as $category)
                        <h5 class="mt-3"><a href="{{ route('category.show', $category['slug']) }}"><i class="{{ $category['icon'] }}"></i> {{ $category['name'] }}</a></h5>
                        @if (count($category['subs']))
                            <ul class="mb-3">
                                @foreach ($category['subs'] as $sub)
                                    <li><a href="{{ route('category.sub.show', [$category['slug'], $sub['slug']]) }}">{{ $sub['name'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    @empty
                        <x-frontend.empty-state message="No categories yet." link-text="back to home" :link-url="route('home')" />
                    @endforelse

                    {{-- TODO: replace with the full product listing once products exist. --}}
                </div>
            </div>
        </div>
    </section>
@endsection
