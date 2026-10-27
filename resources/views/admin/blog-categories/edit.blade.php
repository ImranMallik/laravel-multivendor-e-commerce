@extends('admin.layouts.app')

@section('title', 'Edit Blog Category')
@section('page-title', 'Edit Blog Category')

@section('content')
    @include('admin.blog-categories.partials.form', [
        'action' => route('admin.blog-categories.update', $category),
        'method' => 'PUT',
        'category' => $category,
    ])
@endsection
