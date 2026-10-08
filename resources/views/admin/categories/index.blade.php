@extends('admin.layouts.app')

@section('title', 'Categories')
@section('page-title', 'Categories')

@push('styles')
    {{-- The table shows the chosen icons, so it needs the same Font Awesome (5.15.1) as the picker and the storefront. --}}
    <link rel="stylesheet" href="{{ asset('admin-assets/modules/fontawesome-5.15.1/css/all.min.css') }}">
@endpush

@section('page-actions')
    <x-admin.button :href="route('admin.categories.create')" icon="fas fa-plus">Add Category</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="All categories">
        @include('admin.partials.crud-table', [
            'id' => 'categories-table',
            'source' => route('admin.categories.data'),
            'noun' => 'category',
            'headers' => ['#', 'Icon / Image', 'Name', 'Sub categories', 'Order', 'Status'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'visual', 'orderable' => false, 'searchable' => false],
                ['data' => 'name'],
                ['data' => 'sub_categories_count'],
                ['data' => 'sort_order'],
                ['data' => 'is_active'],
            ],
            'orderColumn' => 4,
        ])
    </x-admin.card>
@endsection
