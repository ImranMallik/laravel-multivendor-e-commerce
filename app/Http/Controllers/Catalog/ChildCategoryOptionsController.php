<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feeds the dependent Child Category dropdown of the admin and vendor product forms.
 */
class ChildCategoryOptionsController extends Controller
{
    /**
     * Active child categories of one sub category. `?include={id}` also lists that child category when
     * it is inactive, so an edit form still shows its current value.
     */
    public function __invoke(Request $request, SubCategory $subCategory): JsonResponse
    {
        $include = $request->integer('include');

        $children = $subCategory->childCategories()
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->when($include, fn ($q) => $q->orWhere('id', $include)))
            ->ordered()
            ->get(['id', 'name']);

        return response()->json($children);
    }
}
