@extends('admin.layouts.app')

@section('title', 'Add Brand')
@section('page-title', 'Add Brand')

@section('content')
    @include('admin.brands.partials.form', ['action' => route('admin.brands.store')])
@endsection
