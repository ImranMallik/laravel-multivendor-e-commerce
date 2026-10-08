@extends('admin.layouts.app')

@section('title', 'Edit Child Category')
@section('page-title', 'Edit Child Category')

@section('page-actions')
    <x-admin.button :href="route('admin.child-categories.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    @include('admin.child-categories.partials.form', [
        'action' => route('admin.child-categories.update', $childCategory),
        'method' => 'PUT',
        'categories' => $categories,
        'childCategory' => $childCategory,
    ])
@endsection
