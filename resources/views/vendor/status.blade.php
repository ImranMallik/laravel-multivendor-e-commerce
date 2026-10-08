@extends('dashboard.layouts.app')

@section('title', 'Application Status')
@section('page-title', 'application status')

@section('content')
    <div class="wsus__dashboard">
        <div class="wsus__message text-center py-5">
            @switch($vendor->status)
                @case(\App\Enums\VendorStatus::Pending)
                    <h4><i class="far fa-hourglass"></i> Waiting for approval</h4>
                    <p class="mt-3">Thanks for registering <strong>{{ $vendor->shop_name }}</strong>. Our team is reviewing your application. You will get an email as soon as there is a decision.</p>
                    @break

                @case(\App\Enums\VendorStatus::Rejected)
                    <h4 class="text-danger"><i class="far fa-times-circle"></i> Application not approved</h4>
                    <p class="mt-3">Unfortunately <strong>{{ $vendor->shop_name }}</strong> was not approved.</p>
                    <p class="mt-2"><strong>Reason:</strong> {{ $vendor->rejection_reason }}</p>
                    @break

                @case(\App\Enums\VendorStatus::Suspended)
                    <h4 class="text-warning"><i class="far fa-ban"></i> Shop suspended</h4>
                    <p class="mt-3"><strong>{{ $vendor->shop_name }}</strong> is currently suspended. Please contact support if you think this is a mistake.</p>
                    @break
            @endswitch
        </div>
    </div>
@endsection
