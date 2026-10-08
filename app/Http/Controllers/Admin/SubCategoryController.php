<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Category\DeleteSubCategoryAction;
use App\Actions\Category\StoreSubCategoryAction;
use App\Actions\Category\ToggleActiveAction;
use App\Actions\Category\UpdateSubCategoryAction;
use App\DataTables\SubCategoryDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubCategoryRequest;
use App\Http\Requests\Admin\TableRequest;
use App\Models\Category;
use App\Models\SubCategory;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.sub-categories.index');
    }

    public function data(TableRequest $request, SubCategoryDataTable $table): JsonResponse
    {
        return $table->json($request);
    }

    public function create(): View
    {
        return view('admin.sub-categories.create', ['categories' => $this->categoryOptions()]);
    }

    public function store(SubCategoryRequest $request, StoreSubCategoryAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()->route('admin.sub-categories.index')->with('success', 'Sub category created.');
    }

    public function edit(SubCategory $subCategory): View
    {
        return view('admin.sub-categories.edit', [
            'subCategory' => $subCategory,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(SubCategoryRequest $request, SubCategory $subCategory, UpdateSubCategoryAction $action): RedirectResponse
    {
        $action->execute($subCategory, $request->validated());

        return redirect()->route('admin.sub-categories.index')->with('success', 'Sub category updated.');
    }

    public function status(SubCategory $subCategory, ToggleActiveAction $action): JsonResponse
    {
        $subCategory = $action->execute($subCategory);

        return response()->json([
            'message' => $subCategory->is_active ? 'Sub category activated.' : 'Sub category deactivated.',
            'is_active' => $subCategory->is_active,
        ]);
    }

    public function destroy(Request $request, SubCategory $subCategory, DeleteSubCategoryAction $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($subCategory);
        } catch (DomainException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Sub category deleted.'])
            : redirect()->route('admin.sub-categories.index')->with('success', 'Sub category deleted.');
    }

    /**
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        return Category::ordered()->pluck('name', 'id')->all();
    }
}
