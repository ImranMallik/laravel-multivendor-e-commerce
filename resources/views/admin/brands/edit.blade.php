@extends('admin.layouts.app')

@section('title', 'Edit Brand')
@section('page-title', 'Edit Brand')

@section('content')
    @include('admin.brands.partials.form', [
        'action' => route('admin.brands.update', $brand),
        'method' => 'PUT',
        'brand' => $brand,
    ])
@endsection
