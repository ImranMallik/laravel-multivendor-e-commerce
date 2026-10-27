{{--
    Comments of a post (template blog_details.html "wsus__comment_area" + "wsus__post_comment").
    Approved comments only, one level of replies. Replying is for signed-in users (the template's reply
    form has only a text box); guests get a login link. Every new comment starts Pending (BlogCommentService).
    Expects: $blog, $comments (paginator of top-level comments with replies), $preview.
--}}
@php
    $user = auth('web')->user();
    $honeypot = \App\Http\Requests\Frontend\BlogCommentRequest::HONEYPOT;
@endphp

<div class="wsus__comment_area" id="comments">
    <h4>comment <span>{{ str_pad((string) $blog->approved_comments_count, 2, '0', STR_PAD_LEFT) }}</span></h4>

    @if ($errors->has('parent_id'))
        <p class="text-danger">{{ $errors->first('parent_id') }}</p>
    @endif

    @foreach ($comments as $comment)
        @foreach ([$comment, ...$comment->replies] as $entry)
            <div @class(['wsus__main_comment', 'wsus__com_replay' => $entry->parent_id])>
                <div class="wsus__comment_img">
                    <img src="{{ $entry->avatar_url }}" alt="{{ $entry->name }}" class="img-fluid w-100">
                </div>
                <div class="wsus__comment_text replay">
                    <h6>{{ $entry->name }} <span>{{ $entry->created_at->format('d M Y') }}</span></h6>
                    <p>{{ $entry->comment }}</p>
                    @unless ($preview)
                        {{-- Replies stay one level deep: replying to a reply answers the same thread. --}}
                        <a href="#" data-bs-toggle="collapse" data-bs-target="#reply-{{ $entry->id }}">replay</a>
                        <div class="accordion accordion-flush" id="reply-accordion-{{ $entry->id }}">
                            <div class="accordion-item">
                                <div id="reply-{{ $entry->id }}" class="accordion-collapse collapse"
                                     data-bs-parent="#reply-accordion-{{ $entry->id }}">
                                    <div class="accordion-body">
                                        @if ($user)
                                            <form method="POST" action="{{ route('frontend.blog.comments.store', $blog->slug) }}">
                                                @csrf
                                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                                <input type="text" name="{{ $honeypot }}" value="" class="d-none" tabindex="-1" autocomplete="off" aria-hidden="true">
                                                <div class="wsus__riv_edit_single text_area">
                                                    <i class="far fa-edit"></i>
                                                    <textarea cols="3" rows="1" name="comment" placeholder="Your Text" maxlength="2000" required aria-label="Your reply"></textarea>
                                                </div>
                                                <button type="submit" class="common_btn">submit</button>
                                            </form>
                                        @else
                                            <a class="common_btn" href="{{ route('login') }}">log in to reply</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endunless
                </div>
            </div>
        @endforeach
    @endforeach

    @include('frontend.blog.partials.pagination', ['paginator' => $comments])
</div>

@unless ($preview)
    <div class="wsus__post_comment">
        <h4>post a comment</h4>
        <form method="POST" action="{{ route('frontend.blog.comments.store', $blog->slug) }}">
            @csrf
            <input type="text" name="{{ $honeypot }}" value="" class="d-none" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="row">
                <div class="col-xl-6">
                    <div class="wsus__single_com">
                        <input type="text" name="name" placeholder="Name" maxlength="100" aria-label="Name"
                               value="{{ $user?->name ?? old('name') }}" @if ($user) readonly @else required @endif>
                        @error('name')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__single_com">
                        <input type="email" name="email" placeholder="Email" maxlength="255" aria-label="Email"
                               value="{{ $user?->email ?? old('email') }}" @if ($user) readonly @else required @endif>
                        @error('email')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="wsus__single_com">
                        <textarea rows="5" name="comment" placeholder="Your Comment" maxlength="2000" required aria-label="Your comment">{{ old('parent_id') ? '' : old('comment') }}</textarea>
                        @error('comment')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
            <button class="common_btn" type="submit">post comment</button>
        </form>
    </div>
@endunless
