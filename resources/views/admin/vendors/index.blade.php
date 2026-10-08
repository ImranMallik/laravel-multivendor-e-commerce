@extends('admin.layouts.app')

@section('title', 'Vendors')
@section('page-title', 'Vendors')

@section('content')
    {{-- Status tabs: clicking one sets the status filter. Counts refresh with every table load. --}}
    <ul class="nav nav-pills mb-4" id="vendor-status-tabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-status="">All <span class="badge badge-white" data-count="all">{{ $counts['all'] }}</span></a>
        </li>
        @foreach ($statuses as $status)
            <li class="nav-item">
                <a class="nav-link" href="#" data-status="{{ $status->value }}">{{ $status->label() }} <span class="badge badge-white" data-count="{{ $status->value }}">{{ $counts[$status->value] }}</span></a>
            </li>
        @endforeach
    </ul>

    <x-admin.card title="Filters">
        <form id="vendor-filters" class="row">
            <div class="col-md-4">
                <x-admin.form.select name="status" id="filter-status" label="Status" placeholder="All statuses"
                                     :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
            </div>
            <div class="col-md-3">
                <x-admin.form.input name="date_from" id="filter-date-from" type="date" label="Registered from" />
            </div>
            <div class="col-md-3">
                <x-admin.form.input name="date_to" id="filter-date-to" type="date" label="Registered to" />
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <x-admin.button variant="light" icon="fas fa-undo" id="filter-reset" block>Reset</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card title="All vendors">
        <div class="table-responsive">
            <table class="table table-striped w-100" id="vendors-table" data-source="{{ route('admin.vendors.data') }}">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Shop</th>
                        <th>Owner</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-admin.card>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/vendor-actions.js') }}"></script>
    <script src="{{ asset('admin-assets/js/vendors-table.js') }}"></script>
@endpush
