@extends('admin.layouts.app')

@section('title', 'Edit Attribute')
@section('page-title', 'Edit Attribute')

@section('content')
    @include('admin.attributes.partials.form', [
        'action' => route('admin.attributes.update', $attribute),
        'method' => 'PUT',
        'attribute' => $attribute,
    ])
@endsection
