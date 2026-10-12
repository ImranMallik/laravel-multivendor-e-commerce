@extends('admin.layouts.app')

@section('title', 'Add Product')
@section('page-title', 'Add Product')

@section('page-actions')
    <x-admin.button :href="route('admin.products.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    @include('admin.products.partials.form', ['action' => route('admin.products.store')])
@endsection
