@extends('admin.layouts.app')

@section('title', 'Search Terms')
@section('page-title', 'Search Terms')

@section('content')
    <x-admin.card title="What customers search for">
        <p class="text-muted">
            Searches submitted from the storefront search box, grouped by term. Terms marked
            <x-admin.badge color="danger">No results</x-admin.badge> never found a product: products customers want but cannot find.
        </p>
        @include('admin.partials.crud-table', [
            'id' => 'search-terms-table',
            'source' => route('admin.search-terms.data'),
            'noun' => 'search',
            'headers' => ['#', 'Term', 'Times searched', 'Last searched', 'Average results'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'term'],
                ['data' => 'searches', 'searchable' => false],
                ['data' => 'last_searched_at', 'searchable' => false],
                ['data' => 'average_results', 'searchable' => false],
            ],
            'orderColumn' => 2,
            'orderDir' => 'desc',
            'actions' => false,
        ])
    </x-admin.card>
@endsection
