@extends('admin.layouts.app')

@section('title', 'Edit Slider')
@section('page-title', 'Edit Slider')

@section('content')
    @include('admin.sliders.partials.form', [
        'action' => route('admin.sliders.update', $slider),
        'method' => 'PUT',
        'slider' => $slider,
    ])
@endsection
