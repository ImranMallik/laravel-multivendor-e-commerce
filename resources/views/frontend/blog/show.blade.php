{{--
    Blog details (template blog_details.html). Expects: $blog (with category, author, tags, approved
    comment count), $comments (approved top-level comments with replies, paginated), $related, $sidebar,
    $preview (true for the signed admin preview of a draft or scheduled post).
    Only the purified content is printed raw ({!! !!}); everything else is escaped.
--}}
@extends('frontend.layouts.master')

@section('title', ($blog->seo_title ?: $blog->title).' | '.config('app.name'))
@section('meta_description', $blog->meta_description)

@push('meta')
    @if ($preview)
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ route('frontend.blog.show', $blog->slug) }}">
    @endif
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $blog->seo_title ?: $blog->title }}">
    <meta property="og:description" content="{{ $blog->meta_description }}">
    <meta property="og:image" content="{{ $blog->image_url }}">
    <meta property="og:url" content="{{ route('frontend.blog.show', $blog->slug) }}">
    <meta name="twitter:card" content="summary_large_image">
@endpush

@php
    $shareUrl = urlencode(route('frontend.blog.show', $blog->slug));
    $shareTitle = urlencode($blog->title);
    $commentCount = $blog->approved_comments_count;
@endphp

@section('content')
    <x-frontend.breadcrumb title="blog details" :items="['blog' => route('frontend.blog.index'), $blog->category->name => route('frontend.blog.category', $blog->category->slug)]" />

    <!--============================
        BLOGS DETAILS START
    ==============================-->
    <section id="wsus__blog_details">
        <div class="container">
            @if ($preview)
                <div class="alert alert-warning" role="status">
                    Preview: this post is <strong>{{ strtolower($blog->displayState()['label']) }}</strong> and not visible to shoppers yet.
                </div>
            @endif
            <div class="row">
                <div class="col-xxl-9 col-xl-8 col-lg-8">
                    <div class="wsus__main_blog">
                        <div class="wsus__main_blog_img">
                            <img src="{{ $blog->image_url }}" alt="{{ $blog->title }}" class="img-fluid w-100">
                        </div>
                        <p class="wsus__main_blog_header">
                            <span><i class="fas fa-user-tie"></i> by {{ $blog->author_name }}</span>
                            <span><i class="fal fa-calendar-alt"></i> {{ $blog->formatted_date }}</span>
                            <span><i class="fal fa-comment-alt-smile"></i> {{ $commentCount }} {{ str('Comment')->plural($commentCount) }}</span>
                            <span><i class="far fa-eye"></i> {{ $blog->views }} {{ str('View')->plural($blog->views) }}</span>
                        </p>
                        <div class="wsus__description_area">
                            <h1>{{ $blog->title }}</h1>
                            {!! $blog->content !!}
                        </div>
                        <div class="wsus__share_blog">
                            <p>share:</p>
                            <ul>
                                <li><a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a></li>
                                <li><a class="twitter" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&amp;text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Twitter"><i class="fab fa-twitter"></i></a></li>
                                <li><a class="linkedin" href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a></li>
                                <li><a class="pinterest" href="https://pinterest.com/pin/create/button/?url={{ $shareUrl }}&amp;media={{ urlencode($blog->image_url) }}&amp;description={{ $shareTitle }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Pinterest"><i class="fab fa-pinterest-p"></i></a></li>
                            </ul>
                        </div>
                        @if ($related->isNotEmpty())
                            <div class="wsus__related_post">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <h5>related post</h5>
                                    </div>
                                </div>
                                <div class="row blog_det_slider">
                                    @foreach ($related as $post)
                                        <div class="col-xl-3">
                                            @include('frontend.blog.partials.card', ['post' => $post])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @include('frontend.blog.partials.comments')
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-4">
                    @include('frontend.blog.partials.sidebar', $sidebar)
                </div>
            </div>
        </div>
    </section>
    <!--============================
        BLOGS DETAILS END
    ==============================-->
@endsection
