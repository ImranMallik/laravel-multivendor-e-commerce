@extends('admin.layouts.app')

@section('title', 'Blog Categories')
@section('page-title', 'Blog Categories')

@section('page-actions')
    <x-admin.button :href="route('admin.blog-categories.create')" icon="fas fa-plus">Add Category</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="All blog categories">
        @include('admin.partials.crud-table', [
            'id' => 'blog-categories-table',
            'source' => route('admin.blog-categories.data'),
            'noun' => 'category',
            'headers' => ['#', 'Name', 'Posts', 'Status', 'Order', 'Created'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'name'],
                ['data' => 'blogs_count', 'searchable' => false],
                ['data' => 'is_active'],
                ['data' => 'sort_order'],
                ['data' => 'created_at'],
            ],
            'orderColumn' => 4,
        ])
    </x-admin.card>
@endsection
