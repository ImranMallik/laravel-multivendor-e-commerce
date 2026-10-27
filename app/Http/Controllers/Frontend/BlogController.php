<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Repositories\BlogRepository;
use App\Services\BlogViewCounter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private readonly BlogRepository $blogs) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $search = mb_substr($search, 0, 100);

        return view('frontend.blog.index', [
            'posts' => $this->blogs->paginate($search ?: null),
            'heading' => $search !== '' ? 'search: '.$search : 'our latest blogs',
            'breadcrumb' => ['blogs' => null],
            'empty' => $search !== '' ? 'No blog posts match “'.$search.'”.' : 'No blog posts yet. Please check back soon.',
        ]);
    }

    public function category(string $slug): View
    {
        $category = BlogCategory::active()->where('slug', $slug)->firstOrFail();

        return view('frontend.blog.index', [
            'posts' => $this->blogs->paginate(category: $category),
            'heading' => $category->name,
            'breadcrumb' => ['blogs' => route('frontend.blog.index'), $category->name => null],
            'empty' => 'No posts in this category yet.',
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = BlogTag::where('slug', $slug)->firstOrFail();

        return view('frontend.blog.index', [
            'posts' => $this->blogs->paginate(tag: $tag),
            'heading' => '#'.$tag->name,
            'breadcrumb' => ['blogs' => route('frontend.blog.index'), $tag->name => null],
            'empty' => 'No posts with this tag yet.',
        ]);
    }

    public function show(Request $request, string $slug, BlogViewCounter $views): View
    {
        $blog = $this->blogs->findVisibleBySlug($slug);
        $views->count($blog, $request);

        return $this->details($blog, false);
    }

    /**
     * Admin preview (signed link from the admin panel): drafts and scheduled posts too, never indexed,
     * views not counted.
     */
    public function preview(Blog $blog): View
    {
        return $this->details($this->blogs->findForPreview($blog->id), true);
    }

    private function details(Blog $blog, bool $preview): View
    {
        return view('frontend.blog.show', [
            'blog' => $blog,
            'comments' => $this->blogs->comments($blog),
            'related' => $this->blogs->related($blog),
            'sidebar' => $this->blogs->sidebar(),
            'preview' => $preview,
        ]);
    }
}
