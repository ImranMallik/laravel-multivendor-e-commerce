<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Category\DeleteCategoryAction;
use App\Actions\Category\StoreCategoryAction;
use App\Actions\Category\ToggleActiveAction;
use App\Actions\Category\UpdateCategoryAction;
use App\DataTables\CategoryDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Requests\Admin\TableRequest;
use App\Models\Category;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index');
    }

    public function data(TableRequest $request, CategoryDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(CategoryRequest $request, StoreCategoryAction $action): RedirectResponse
    {
        $action->execute($request->safe()->except(['image', 'remove_image']), $request->file('image'));

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $action->execute(
            $category,
            $request->safe()->except(['image', 'remove_image']),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function status(Category $category, ToggleActiveAction $action): JsonResponse
    {
        $category = $action->execute($category);

        return response()->json([
            'message' => $category->is_active ? 'Category activated.' : 'Category deactivated.',
            'is_active' => $category->is_active,
        ]);
    }

    public function destroy(Request $request, Category $category, DeleteCategoryAction $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($category);
        } catch (DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Category deleted.'])
            : redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
