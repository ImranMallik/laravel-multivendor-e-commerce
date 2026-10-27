{{--
    Blog listing (template blog.html): all posts, a category, a tag or a search (?search=).
    Expects: $posts (paginator), $heading, $breadcrumb (label => url|null), $empty.
--}}
@extends('frontend.layouts.master')

@section('title', ucfirst($heading).' | '.config('app.name'))
@section('meta_description', 'Latest posts from the '.config('app.name').' blog.')

@push('meta')
    <link rel="canonical" href="{{ $posts->currentPage() > 1 ? $posts->url($posts->currentPage()) : url()->current() }}">
@endpush

@section('content')
    <x-frontend.breadcrumb :title="$heading" :items="$breadcrumb" />

    <!--============================
        BLOGS PAGE START
    ==============================-->
    <section id="wsus__blogs">
        <div class="container">
            <div class="row">
                @forelse ($posts as $post)
                    <div class="col-xl-4 col-sm-6 col-lg-4 col-xxl-3">
                        @include('frontend.blog.partials.card', ['post' => $post])
                    </div>
                @empty
                    <div class="col-12">
                        <x-frontend.empty-state :message="$empty" link-text="all blog posts" :link-url="route('frontend.blog.index')" icon="far fa-newspaper" />
                    </div>
                @endforelse
            </div>
            @include('frontend.blog.partials.pagination', ['paginator' => $posts])
        </div>
    </section>
    <!--============================
        BLOGS PAGE END
    ==============================-->
@endsection
