<?php

namespace App\DataTables;

use App\Http\Requests\Admin\TableRequest;
use App\Models\SearchLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

/**
 * Server-side data for the admin "Search Terms" report: submitted searches grouped by term.
 * Column keys must match the "columns" in resources/views/admin/search-terms/index.blade.php.
 */
class SearchTermDataTable
{
    public function json(TableRequest $request): JsonResponse
    {
        return DataTables::eloquent($this->query())
            ->addIndexColumn()
            ->editColumn('searches', fn (SearchLog $row) => (int) $row->searches)
            ->editColumn('last_searched_at', fn (SearchLog $row) => $row->last_searched_at
                ? Carbon::parse($row->last_searched_at)->format('d M Y, H:i')
                : '')
            ->editColumn('average_results', fn (SearchLog $row) => view('admin.search-terms.partials.results', [
                'average' => (float) $row->average_results,
                'neverFound' => (int) $row->best_results === 0,
            ])->render())
            // A filter callback replaces yajra's automatic per-column search; only the term is searched.
            ->filter(fn ($query) => $query->when(
                filled($request->input('search.value')),
                fn ($q) => $q->where('term', 'like', '%'.$request->input('search.value').'%'),
            ))
            ->rawColumns(['average_results'])
            ->toJson();
    }

    /**
     * @return Builder<SearchLog>
     */
    private function query(): Builder
    {
        return SearchLog::query()
            ->select('term')
            ->selectRaw('count(*) as searches, max(created_at) as last_searched_at, avg(results_count) as average_results, max(results_count) as best_results')
            ->groupBy('term');
    }
}
