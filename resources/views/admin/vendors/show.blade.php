@extends('admin.layouts.app')

@section('title', $vendor->shop_name)
@section('page-title', $vendor->shop_name)

@section('page-actions')
    <x-admin.button :href="route('admin.vendors.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <x-admin.card title="Shop">
                <x-slot:actions>
                    <x-admin.badge :status="$vendor->status" />
                </x-slot:actions>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <img src="{{ $vendor->hasLogo() ? $vendor->logo_url : asset('admin-assets/img/avatar/avatar-1.png') }}"
                             alt="{{ $vendor->shop_name }}" width="120" height="120" class="rounded admin-img-cover">
                    </div>
                    <div class="col-md-9">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Shop name</dt><dd class="col-sm-8">{{ $vendor->shop_name }}</dd>
                            <dt class="col-sm-4">Shop phone</dt><dd class="col-sm-8">{{ $vendor->phone ?: '—' }}</dd>
                            <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $vendor->address }}</dd>
                            <dt class="col-sm-4">Source</dt><dd class="col-sm-8"><x-admin.badge :status="$vendor->source" /></dd>
                            @if ($vendor->createdBy)
                                <dt class="col-sm-4">Created by</dt><dd class="col-sm-8">{{ $vendor->createdBy->name }}</dd>
                            @endif
                            <dt class="col-sm-4">Applied at</dt><dd class="col-sm-8">{{ $vendor->applied_at?->format('d M Y H:i') ?? '—' }}</dd>
                            <dt class="col-sm-4">Reviewed by</dt>
                            <dd class="col-sm-8">
                                {{ $vendor->reviewedBy?->name ?? '—' }}
                                @if ($vendor->reviewed_at)<span class="text-muted">&middot; {{ $vendor->reviewed_at->format('d M Y H:i') }}</span>@endif
                            </dd>
                            <dt class="col-sm-4">Approved at</dt><dd class="col-sm-8">{{ $vendor->approved_at?->format('d M Y H:i') ?? '—' }}</dd>
                            @if ($vendor->statusReason())
                                <dt class="col-sm-4">{{ $vendor->status->label() }} reason</dt><dd class="col-sm-8">{{ $vendor->statusReason() }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="Owner">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $vendor->user->name }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $vendor->user->email }}</dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $vendor->user->phone ?: '—' }}</dd>
                    <dt class="col-sm-4">Account created</dt><dd class="col-sm-8">{{ $vendor->user->created_at->format('d M Y H:i') }}</dd>
                    <dt class="col-sm-4">Account status</dt><dd class="col-sm-8">{{ ucfirst($vendor->user->status->value) }}</dd>
                </dl>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Actions">
                @if ($vendor->status->availableActions())
                    {{-- Only the actions allowed for this status (VendorStatus::availableActions()); same AJAX handler as the list. --}}
                    @include('admin.vendors.partials.actions', ['vendor' => $vendor, 'view' => false, 'size' => null])
                @else
                    <p class="text-muted mb-0">No admin action is available. A rejected vendor can apply again from their account.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Status history">
                @include('admin.vendors.partials.history', ['logs' => $vendor->statusLogs])
            </x-admin.card>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/vendor-actions.js') }}"></script>
@endpush
