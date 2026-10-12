@extends('admin.layouts.app')

@section('title', 'Add Attribute')
@section('page-title', 'Add Attribute')

@section('content')
    @include('admin.attributes.partials.form', ['action' => route('admin.attributes.store')])
@endsection
