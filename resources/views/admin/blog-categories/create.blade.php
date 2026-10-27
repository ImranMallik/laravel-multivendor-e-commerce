@extends('admin.layouts.app')

@section('title', 'Add Blog Category')
@section('page-title', 'Add Blog Category')

@section('content')
    @include('admin.blog-categories.partials.form', ['action' => route('admin.blog-categories.store')])
@endsection
