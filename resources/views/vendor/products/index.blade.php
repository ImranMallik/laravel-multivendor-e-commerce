@extends('dashboard.layouts.app')

@section('title', 'My Products')
@section('page-title', 'products')

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend-assets/vendor/datatables/css/dataTables.bootstrap4.min.css') }}">
@endpush

@section('content')
    <div class="wsus__dashboard_profile">
        <div class="wsus__dash_pro_area">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="mb-0">all products</h4>
                <a class="common_btn" href="{{ route('vendor.products.create') }}"><i class="far fa-plus"></i> add product</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle w-100" id="vendor-products-table" data-source="{{ route('vendor.products.data') }}">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name / SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Active</th>
                            <th>Approval</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('frontend-assets/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('frontend-assets/vendor/datatables/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('frontend-assets/js/vendor-products-table.js') }}"></script>
@endpush
