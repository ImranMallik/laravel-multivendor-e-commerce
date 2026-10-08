@extends('admin.layouts.app')

@section('title', 'Sub Categories')
@section('page-title', 'Sub Categories')

@section('page-actions')
    <x-admin.button :href="route('admin.sub-categories.create')" icon="fas fa-plus">Add Sub Category</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="All sub categories">
        @include('admin.partials.crud-table', [
            'id' => 'sub-categories-table',
            'source' => route('admin.sub-categories.data'),
            'noun' => 'sub category',
            'headers' => ['#', 'Name', 'Category', 'Child categories', 'Order', 'Status'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'name'],
                ['data' => 'category'],
                ['data' => 'child_categories_count'],
                ['data' => 'sort_order'],
                ['data' => 'is_active'],
            ],
            'orderColumn' => 4,
        ])
    </x-admin.card>
@endsection
