{{--
    Blog sidebar (template blog_details.html): search, categories, popular posts, popular tags.
    Expects: $categories, $popular (with approved_comments_count), $tags — from BlogRepository::sidebar() (cached).
--}}
<div class="wsus__blog_sidebar" id="sticky_sidebar">
    <div class="wsus__blog_search">
        <h4>search</h4>
        <form method="GET" action="{{ route('frontend.blog.index') }}" role="search">
            <input type="text" name="search" placeholder="Search" value="{{ request('search') }}" maxlength="100" aria-label="Search the blog">
            <button type="submit" class="common_btn" aria-label="Search"><i class="far fa-search"></i></button>
        </form>
    </div>
    @if ($categories->isNotEmpty())
        <div class="wsus__blog_category">
            <h4>Categories</h4>
            <ul>
                @foreach ($categories as $category)
                    <li><a href="{{ route('frontend.blog.category', $category->slug) }}">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($popular->isNotEmpty())
        <div class="wsus__blog_post">
            <h4>Popular Post</h4>
            @foreach ($popular as $post)
                <div class="wsus__blog_post_single">
                    <a href="{{ route('frontend.blog.show', $post->slug) }}" class="wsus__blog_post_img">
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="imgofluid w-100" loading="lazy">
                    </a>
                    <div class="wsus__blog_post_text">
                        <a href="{{ route('frontend.blog.show', $post->slug) }}" title="{{ $post->title }}">{{ $post->title }}</a>
                        <p> <span>{{ ucfirst($post->formatted_date) }} </span> {{ $post->approved_comments_count }} {{ str('Comment')->plural($post->approved_comments_count) }} </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @if ($tags->isNotEmpty())
        <div class="wsus__popular_tag">
            <h4>popular tags</h4>
            <ul>
                @foreach ($tags as $tag)
                    <li><a href="{{ route('frontend.blog.tag', $tag->slug) }}">{{ $tag->name }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
