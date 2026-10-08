@extends('admin.layouts.app')

@section('title', 'Add Child Category')
@section('page-title', 'Add Child Category')

@section('page-actions')
    <x-admin.button :href="route('admin.child-categories.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    @include('admin.child-categories.partials.form', [
        'action' => route('admin.child-categories.store'),
        'categories' => $categories,
    ])
@endsection
