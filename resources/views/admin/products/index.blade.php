@extends('admin.layouts.app')

@section('title', $pendingOnly ? 'Pending Products' : 'Products')
@section('page-title', $pendingOnly ? 'Pending Products' : 'Products')

@section('page-actions')
    <x-admin.button :href="route('admin.products.create')" icon="fas fa-plus">Add Product</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="Filters">
        <form id="product-filters" class="row">
            <div class="col-md-4">
                <x-admin.form.select name="vendor" id="filter-vendor" label="Vendor" placeholder="All vendors"
                                     :options="['inhouse' => 'In-house'] + $vendorOptions" />
            </div>
            <div class="col-md-3">
                <x-admin.form.select name="category_id" id="filter-category" label="Category" placeholder="All categories"
                                     :options="$categoryOptions" />
            </div>
            <div class="col-md-3">
                @if ($pendingOnly)
                    <input type="hidden" id="filter-approval" value="pending">
                    <div class="form-group">
                        <label>Approval</label>
                        <p class="form-control-plaintext"><x-admin.badge :status="\App\Enums\ProductApprovalStatus::Pending" /></p>
                    </div>
                @else
                    <x-admin.form.select name="approval_status" id="filter-approval" label="Approval" placeholder="Any approval"
                                         :options="\App\Enums\ProductApprovalStatus::options()" />
                @endif
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <x-admin.button variant="light" icon="fas fa-undo" id="filter-reset" block>Reset</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card :title="$pendingOnly ? 'Products waiting for approval' : 'All products'">
        <div class="table-responsive">
            <table class="table table-striped w-100" id="products-table" data-source="{{ route('admin.products.data') }}">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name / SKU</th>
                        <th>Vendor</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Active</th>
                        <th>Approval</th>
                        <th>Home flags</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-admin.card>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/product-actions.js') }}"></script>
    <script src="{{ asset('admin-assets/js/products-table.js') }}"></script>
@endpush
