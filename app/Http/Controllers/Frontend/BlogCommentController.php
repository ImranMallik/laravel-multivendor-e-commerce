<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\BlogCommentRequest;
use App\Services\BlogCommentService;
use Illuminate\Http\RedirectResponse;

class BlogCommentController extends Controller
{
    private const THANKS = 'Thank you! Your comment will appear after it has been approved.';

    public function store(BlogCommentRequest $request, BlogCommentService $comments): RedirectResponse
    {
        $blog = $request->blog();
        $back = redirect()->to(route('frontend.blog.show', $blog->slug).'#comments');

        // Honeypot filled in: answer like a success so the bot learns nothing, but store nothing.
        if ($request->isSpamBot()) {
            return $back->with('success', self::THANKS);
        }

        $comments->submit($blog, $request->validated(), $request->user('web'), $request->ip());

        return $back->with('success', self::THANKS);
    }
}
