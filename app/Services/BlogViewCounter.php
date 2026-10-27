<?php

namespace App\Services;

use App\Models\Blog;
use Illuminate\Http\Request;

/**
 * Counts a post view once per visitor session, and never for obvious bots. A plain UPDATE (no model
 * events), so counting a view does not clear the blog cache.
 */
class BlogViewCounter
{
    private const SESSION_KEY = 'blog_viewed';

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless/i';

    public function count(Blog $blog, Request $request): void
    {
        if ($this->isBot($request)) {
            return;
        }

        $seen = (array) $request->session()->get(self::SESSION_KEY, []);

        if (in_array($blog->id, $seen, true)) {
            return;
        }

        Blog::query()->whereKey($blog->id)->increment('views');
        $blog->views++;

        $request->session()->put(self::SESSION_KEY, array_slice([...$seen, $blog->id], -200));
    }

    private function isBot(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match(self::BOT_PATTERN, $agent) === 1;
    }
}
