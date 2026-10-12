@extends('admin.layouts.app')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('page-actions')
    <x-admin.button :href="route('admin.products.show', $product)" variant="light" icon="fas fa-arrow-left">Back to product</x-admin.button>
@endsection

@section('content')
    @include('admin.products.partials.form', [
        'action' => route('admin.products.update', $product),
        'method' => 'PUT',
        'product' => $product,
    ])
@endsection
