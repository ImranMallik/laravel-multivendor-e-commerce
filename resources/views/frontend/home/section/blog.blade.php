{{-- "recent blogs": published posts marked "show on home", newest first ($blogPosts from HomeController, cached). --}}
@if ($blogPosts->isNotEmpty())
    <section id="wsus__blogs" class="home_blogs">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="wsus__section_header">
                        <h3>recent blogs</h3>
                        <a class="see_btn" href="{{ route('frontend.blog.index') }}">see more <i
                                class="fas fa-caret-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="row home_blog_slider">
                @foreach ($blogPosts as $post)
                    <div class="col-xl-3">
                        @include('frontend.blog.partials.card', ['post' => $post])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
