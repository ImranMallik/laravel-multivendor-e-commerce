<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SearchTermDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TableRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Read-only report of what customers search for on the storefront (SearchLogger).
 */
class SearchTermController extends Controller
{
    public function index(): View
    {
        return view('admin.search-terms.index');
    }

    public function data(TableRequest $request, SearchTermDataTable $table): JsonResponse
    {
        return $table->json($request);
    }
}
