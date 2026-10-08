@extends('admin.layouts.app')

@section('title', 'Sliders')
@section('page-title', 'Sliders')

@section('page-actions')
    <x-admin.button :href="route('admin.sliders.create')" icon="fas fa-plus">Add Slider</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="Home banner slides">
        <div class="table-responsive">
            <table class="table table-striped w-100" id="sliders-table" data-source="{{ route('admin.sliders.data') }}">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Button</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-admin.card>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/sliders-table.js') }}"></script>
@endpush
