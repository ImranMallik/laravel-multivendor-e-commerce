<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\SearchSuggestionRequest;
use App\Services\ProductSearchService;
use Illuminate\Http\JsonResponse;

class SearchSuggestionController extends Controller
{
    /**
     * Products, categories and brands for the dropdown under the search box (plain text only: names come as
     * parts flagged "match", and search.js escapes everything it shows).
     */
    public function __invoke(SearchSuggestionRequest $request, ProductSearchService $search): JsonResponse
    {
        return response()->json($search->suggestions($request->term(), $search->findCategory($request->categorySlug())));
    }
}
