@extends('admin.layouts.app')

@section('title', 'Add Category')
@section('page-title', 'Add Category')

@section('page-actions')
    <x-admin.button :href="route('admin.categories.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    @include('admin.categories.partials.form', ['action' => route('admin.categories.store')])
@endsection
