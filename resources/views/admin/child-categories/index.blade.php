@extends('admin.layouts.app')

@section('title', 'Child Categories')
@section('page-title', 'Child Categories')

@section('page-actions')
    <x-admin.button :href="route('admin.child-categories.create')" icon="fas fa-plus">Add Child Category</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="All child categories">
        @include('admin.partials.crud-table', [
            'id' => 'child-categories-table',
            'source' => route('admin.child-categories.data'),
            'noun' => 'child category',
            'headers' => ['#', 'Name', 'Category', 'Sub category', 'Order', 'Status'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'name'],
                ['data' => 'category'],
                ['data' => 'sub_category'],
                ['data' => 'sort_order'],
                ['data' => 'is_active'],
            ],
            'orderColumn' => 4,
        ])
    </x-admin.card>
@endsection
