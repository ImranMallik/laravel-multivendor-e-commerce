{{--
    One blog card (template "wsus__single_blog wsus__single_blog_2"), used by the home slider, the blog
    listing and the related posts slider. The column wrapper stays in the calling view (the template
    uses different columns in each place). Expects: $post (with category loaded).
--}}
<div class="wsus__single_blog wsus__single_blog_2">
    <a class="wsus__blog_img" href="{{ route('frontend.blog.show', $post->slug) }}">
        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="img-fluid w-100" loading="lazy">
    </a>
    <div class="wsus__blog_text">
        <a class="blog_top {{ $post->category->labelColor() }}" href="{{ route('frontend.blog.category', $post->category->slug) }}">{{ $post->category->name }}</a>
        <div class="wsus__blog_text_center">
            <a href="{{ route('frontend.blog.show', $post->slug) }}" title="{{ $post->title }}">{{ $post->title }}</a>
            <p class="date">{{ $post->formatted_date }}</p>
        </div>
    </div>
</div>
