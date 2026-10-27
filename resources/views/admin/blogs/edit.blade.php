@extends('admin.layouts.app')

@section('title', 'Edit Blog Post')
@section('page-title', 'Edit Blog Post')

@section('content')
    @include('admin.blogs.partials.form', [
        'action' => route('admin.blogs.update', $blog),
        'method' => 'PUT',
        'blog' => $blog,
    ])
@endsection
