@extends('admin.layouts.app')

@section('title', 'Brands')
@section('page-title', 'Brands')

@section('page-actions')
    <x-admin.button :href="route('admin.brands.create')" icon="fas fa-plus">Add Brand</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="All brands">
        @include('admin.partials.crud-table', [
            'id' => 'brands-table',
            'source' => route('admin.brands.data'),
            'noun' => 'brand',
            'headers' => ['#', 'Logo', 'Name', 'Featured', 'Status', 'Order', 'Created'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'logo', 'orderable' => false, 'searchable' => false],
                ['data' => 'name'],
                ['data' => 'is_featured'],
                ['data' => 'is_active'],
                ['data' => 'sort_order'],
                ['data' => 'created_at'],
            ],
            'orderColumn' => 5,
        ])
    </x-admin.card>
@endsection
