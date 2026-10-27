@extends('admin.layouts.app')

@section('title', 'Add Blog Post')
@section('page-title', 'Add Blog Post')

@section('content')
    @include('admin.blogs.partials.form', ['action' => route('admin.blogs.store')])
@endsection
