<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feeds the dependent Sub Category dropdown of the child category form.
 */
class CategorySubCategoryController extends Controller
{
    /**
     * Active sub categories of one category. `?include={id}` also lists that sub category when it is
     * inactive, so editing a child whose sub category was deactivated still shows its current value.
     */
    public function __invoke(Request $request, Category $category): JsonResponse
    {
        $include = $request->integer('include');

        $subCategories = $category->subCategories()
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->when($include, fn ($q) => $q->orWhere('id', $include)))
            ->ordered()
            ->get(['id', 'name']);

        return response()->json($subCategories);
    }
}
