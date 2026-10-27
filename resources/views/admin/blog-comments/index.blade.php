@extends('admin.layouts.app')

@section('title', 'Blog Comments')
@section('page-title', 'Blog Comments')

@section('content')
    <x-admin.card title="All comments (pending first)">
        @include('admin.partials.crud-table', [
            'id' => 'blog-comments-table',
            'source' => route('admin.blog-comments.data'),
            'noun' => 'comment',
            'headers' => ['#', 'Author', 'Comment', 'Post', 'Status', 'Date'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'author', 'orderable' => false, 'searchable' => false],
                ['data' => 'comment', 'orderable' => false],
                ['data' => 'post', 'orderable' => false, 'searchable' => false],
                ['data' => 'status'],
                ['data' => 'created_at'],
            ],
            'orderColumn' => 5,
        ])
    </x-admin.card>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/blog-comments.js') }}"></script>
@endpush
