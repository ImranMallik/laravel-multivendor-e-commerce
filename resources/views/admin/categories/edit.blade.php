@extends('admin.layouts.app')

@section('title', 'Edit Category')
@section('page-title', 'Edit Category')

@section('page-actions')
    <x-admin.button :href="route('admin.categories.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    @include('admin.categories.partials.form', [
        'action' => route('admin.categories.update', $category),
        'method' => 'PUT',
        'category' => $category,
    ])
@endsection
