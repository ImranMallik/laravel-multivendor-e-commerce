<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Category\DeleteChildCategoryAction;
use App\Actions\Category\StoreChildCategoryAction;
use App\Actions\Category\ToggleActiveAction;
use App\Actions\Category\UpdateChildCategoryAction;
use App\DataTables\ChildCategoryDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChildCategoryRequest;
use App\Http\Requests\Admin\TableRequest;
use App\Models\Category;
use App\Models\ChildCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.child-categories.index');
    }

    public function data(TableRequest $request, ChildCategoryDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.child-categories.create', ['categories' => $this->categoryOptions()]);
    }

    public function store(ChildCategoryRequest $request, StoreChildCategoryAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()->route('admin.child-categories.index')->with('success', 'Child category created.');
    }

    public function edit(ChildCategory $childCategory): View
    {
        return view('admin.child-categories.edit', [
            'childCategory' => $childCategory,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(ChildCategoryRequest $request, ChildCategory $childCategory, UpdateChildCategoryAction $action): RedirectResponse
    {
        $action->execute($childCategory, $request->validated());

        return redirect()->route('admin.child-categories.index')->with('success', 'Child category updated.');
    }

    public function status(ChildCategory $childCategory, ToggleActiveAction $action): JsonResponse
    {
        $childCategory = $action->execute($childCategory);

        return response()->json([
            'message' => $childCategory->is_active ? 'Child category activated.' : 'Child category deactivated.',
            'is_active' => $childCategory->is_active,
        ]);
    }

    public function destroy(Request $request, ChildCategory $childCategory, DeleteChildCategoryAction $action): JsonResponse|RedirectResponse
    {
        $action->execute($childCategory);

        return $request->expectsJson()
            ? response()->json(['message' => 'Child category deleted.'])
            : redirect()->route('admin.child-categories.index')->with('success', 'Child category deleted.');
    }

    /**
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        return Category::ordered()->pluck('name', 'id')->all();
    }
}
