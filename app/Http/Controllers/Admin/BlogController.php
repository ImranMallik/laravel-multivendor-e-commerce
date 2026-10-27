<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\BlogDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogEditorImageRequest;
use App\Http\Requests\Admin\BlogRequest;
use App\Http\Requests\Admin\TableRequest;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class BlogController extends Controller
{
    /** How long an admin preview link of a draft stays valid. */
    private const PREVIEW_MINUTES = 30;

    public function __construct(private readonly BlogService $blogs) {}

    public function index(): View
    {
        return view('admin.blogs.index');
    }

    public function data(TableRequest $request, BlogDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.blogs.create', ['categories' => $this->categoryOptions()]);
    }

    public function store(BlogRequest $request): RedirectResponse
    {
        $this->blogs->create($request->safe()->except('image'), $request->file('image'), $request->user('admin'));

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post created.');
    }

    public function edit(Blog $blog): View
    {
        $blog->load('tags:id,name');

        return view('admin.blogs.edit', ['blog' => $blog, 'categories' => $this->categoryOptions($blog)]);
    }

    public function update(BlogRequest $request, Blog $blog): RedirectResponse
    {
        $this->blogs->update($blog, $request->safe()->except('image'), $request->file('image'));

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post updated.');
    }

    public function showOnHome(Blog $blog): JsonResponse
    {
        $blog = $this->blogs->toggleShowOnHome($blog);

        return response()->json([
            'message' => $blog->show_on_home ? 'Shown on the home page.' : 'Removed from the home page.',
            'show_on_home' => $blog->show_on_home,
        ]);
    }

    /**
     * Opens the post on the storefront through a short-lived signed link, so drafts and scheduled posts
     * can be checked without making them public.
     */
    public function preview(Blog $blog): RedirectResponse
    {
        return redirect()->away(URL::temporarySignedRoute('frontend.blog.preview', now()->addMinutes(self::PREVIEW_MINUTES), ['blog' => $blog->id]));
    }

    public function editorImage(BlogEditorImageRequest $request): JsonResponse
    {
        return response()->json(['url' => $this->blogs->uploadContentImage($request->file('image'))]);
    }

    public function destroy(Request $request, Blog $blog): JsonResponse|RedirectResponse
    {
        $this->blogs->delete($blog);

        return $request->expectsJson()
            ? response()->json(['message' => 'Blog post deleted.'])
            : redirect()->route('admin.blogs.index')->with('success', 'Blog post deleted.');
    }

    /**
     * Active categories for the select, plus the current category of the post when it has been switched off.
     *
     * @return array<int, string>
     */
    private function categoryOptions(?Blog $blog = null): array
    {
        return BlogCategory::query()
            ->where(fn ($query) => $query->active()->when($blog, fn ($q) => $q->orWhere('id', $blog->blog_category_id)))
            ->ordered()
            ->pluck('name', 'id')
            ->all();
    }
}
