@extends('admin.layouts.app')

@section('title', $vendor->shop_name)
@section('page-title', $vendor->shop_name)

@section('page-actions')
    <x-admin.button :href="route('admin.vendors.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <x-admin.card title="Shop details">
                <x-slot:actions>
                    <x-admin.badge :status="$vendor->status" />
                </x-slot:actions>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Shop name</dt><dd class="col-sm-8">{{ $vendor->shop_name }}</dd>
                    <dt class="col-sm-4">Slug</dt><dd class="col-sm-8">{{ $vendor->slug }}</dd>
                    <dt class="col-sm-4">Shop phone</dt><dd class="col-sm-8">{{ $vendor->phone ?: '—' }}</dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $vendor->address }}</dd>
                    <dt class="col-sm-4">Description</dt><dd class="col-sm-8">{{ $vendor->description ?: '—' }}</dd>
                    <dt class="col-sm-4">Registered</dt><dd class="col-sm-8">{{ $vendor->created_at->format('d M Y H:i') }}</dd>
                    <dt class="col-sm-4">Approved at</dt><dd class="col-sm-8">{{ $vendor->approved_at?->format('d M Y H:i') ?? '—' }}</dd>
                    @if ($vendor->rejection_reason)
                        <dt class="col-sm-4">Rejection reason</dt><dd class="col-sm-8">{{ $vendor->rejection_reason }}</dd>
                    @endif
                </dl>
            </x-admin.card>

            <x-admin.card title="Owner">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $vendor->user->name }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $vendor->user->email }}</dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $vendor->user->phone ?: '—' }}</dd>
                    <dt class="col-sm-4">Account</dt><dd class="col-sm-8">{{ ucfirst($vendor->user->status->value) }}</dd>
                </dl>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Actions">
                {{-- Same buttons and AJAX handler as the list; the page reloads after an action. --}}
                @include('admin.vendors.partials.actions', ['vendor' => $vendor, 'view' => false, 'size' => null])
            </x-admin.card>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/vendor-actions.js') }}"></script>
@endpush
