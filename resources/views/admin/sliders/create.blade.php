@extends('admin.layouts.app')

@section('title', 'Add Slider')
@section('page-title', 'Add Slider')

@section('content')
    @include('admin.sliders.partials.form', ['action' => route('admin.sliders.store')])
@endsection
