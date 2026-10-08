@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <x-admin.card>
        Welcome back, {{ auth('admin')->user()->name }}.
    </x-admin.card>
@endsection
