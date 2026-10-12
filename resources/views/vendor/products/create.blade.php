@extends('dashboard.layouts.app')

@section('title', 'Add Product')
@section('page-title', 'add product')

@section('content')
    @if ($reviewRequired)
        <div class="alert alert-info">New products are checked by our team before they appear in the store. You will get an email when yours is approved.</div>
    @endif

    @include('vendor.products.partials.form', ['action' => route('vendor.products.store')])
@endsection
