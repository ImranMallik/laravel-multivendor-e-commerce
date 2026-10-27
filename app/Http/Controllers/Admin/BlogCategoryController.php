<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\BlogCategoryDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogCategoryRequest;
use App\Http\Requests\Admin\TableRequest;
use App\Models\BlogCategory;
use App\Services\BlogCategoryService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function __construct(private readonly BlogCategoryService $categories) {}

    public function index(): View
    {
        return view('admin.blog-categories.index');
    }

    public function data(TableRequest $request, BlogCategoryDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.blog-categories.create');
    }

    public function store(BlogCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()->route('admin.blog-categories.index')->with('success', 'Blog category created.');
    }

    public function edit(BlogCategory $blogCategory): View
    {
        return view('admin.blog-categories.edit', ['category' => $blogCategory]);
    }

    public function update(BlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $this->categories->update($blogCategory, $request->validated());

        return redirect()->route('admin.blog-categories.index')->with('success', 'Blog category updated.');
    }

    public function status(BlogCategory $blogCategory): JsonResponse
    {
        $category = $this->categories->toggleActive($blogCategory);

        return response()->json([
            'message' => $category->is_active ? 'Category activated.' : 'Category deactivated.',
            'is_active' => $category->is_active,
        ]);
    }

    public function destroy(Request $request, BlogCategory $blogCategory): JsonResponse|RedirectResponse
    {
        try {
            $this->categories->delete($blogCategory);
        } catch (DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Blog category deleted.'])
            : redirect()->route('admin.blog-categories.index')->with('success', 'Blog category deleted.');
    }
}
