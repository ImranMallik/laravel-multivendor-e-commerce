@extends('admin.layouts.app')

@section('title', 'Blog Posts')
@section('page-title', 'Blog Posts')

@section('page-actions')
    <x-admin.button :href="route('admin.blogs.create')" icon="fas fa-plus">Add Post</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="All posts">
        @include('admin.partials.crud-table', [
            'id' => 'blogs-table',
            'source' => route('admin.blogs.data'),
            'noun' => 'post',
            'headers' => ['#', 'Image', 'Title', 'Category', 'Status', 'Home', 'Views', 'Published'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'image', 'orderable' => false, 'searchable' => false],
                ['data' => 'title'],
                ['data' => 'category', 'orderable' => false, 'searchable' => false],
                ['data' => 'status', 'orderable' => false, 'searchable' => false],
                ['data' => 'show_on_home', 'searchable' => false],
                ['data' => 'views', 'searchable' => false],
                ['data' => 'published_at', 'searchable' => false],
            ],
            'orderColumn' => 7,
        ])
    </x-admin.card>
@endsection
