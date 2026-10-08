@extends('admin.layouts.app')

@section('title', 'Add Sub Category')
@section('page-title', 'Add Sub Category')

@section('page-actions')
    <x-admin.button :href="route('admin.sub-categories.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    @include('admin.sub-categories.partials.form', [
        'action' => route('admin.sub-categories.store'),
        'categories' => $categories,
    ])
@endsection
