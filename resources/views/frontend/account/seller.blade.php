@extends('dashboard.layouts.app')

@section('title', 'Become a Seller')
@section('page-title', 'become a seller')

@section('content')
    @php($status = $vendor?->status)

    @if (! $vendor)
        <div class="wsus__dashboard">
            <div class="wsus__message">
                <h4><i class="far fa-store"></i> sell on {{ config('app.name') }}</h4>
                <p class="mt-2">Open your own shop with the account you already have. An administrator reviews every application and you will get an email with the decision. You can keep shopping meanwhile.</p>
            </div>
        </div>
        @include('frontend.account.partials.seller-form', ['action' => route('account.seller.apply'), 'button' => 'submit application'])
    @else
        @switch($status)
            @case(\App\Enums\VendorStatus::Pending)
                <div class="wsus__dashboard">
                    <div class="wsus__message text-center py-5">
                        <h4><i class="far fa-hourglass"></i> Your application is under review</h4>
                        <p class="mt-3">Thanks for applying with <strong>{{ $vendor->shop_name }}</strong>. You will get an email as soon as there is a decision.</p>
                    </div>
                    <div class="wsus__message">
                        <h4>submitted details</h4>
                        <div class="row">
                            <div class="col-xl-6"><div class="wsus__single_inout"><label>shop name</label><p>{{ $vendor->shop_name }}</p></div></div>
                            <div class="col-xl-6"><div class="wsus__single_inout"><label>shop phone</label><p>{{ $vendor->phone ?: '—' }}</p></div></div>
                            <div class="col-xl-12"><div class="wsus__single_inout"><label>address</label><p>{{ $vendor->address }}</p></div></div>
                            <div class="col-xl-6"><div class="wsus__single_inout"><label>submitted on</label><p>{{ $vendor->applied_at?->format('d M Y, H:i') ?? '—' }}</p></div></div>
                        </div>
                    </div>
                </div>
                @break

            @case(\App\Enums\VendorStatus::Rejected)
                <div class="wsus__dashboard">
                    <div class="wsus__message text-center py-4">
                        <h4 class="text-danger"><i class="far fa-times-circle"></i> Application not approved</h4>
                        <p class="mt-3">Unfortunately <strong>{{ $vendor->shop_name }}</strong> was not approved.</p>
                        <p class="mt-2"><strong>Reason:</strong> {{ $vendor->statusReason() }}</p>
                        <p class="mt-2">Correct your details below and apply again.</p>
                    </div>
                </div>
                @include('frontend.account.partials.seller-form', [
                    'action' => route('account.seller.reapply'),
                    'method' => 'PUT',
                    'vendor' => $vendor,
                    'button' => 'Re-apply',
                ])
                @break

            @case(\App\Enums\VendorStatus::Approved)
                <div class="wsus__dashboard">
                    <div class="wsus__message text-center py-5">
                        <h4 class="text-success"><i class="far fa-check-circle"></i> You're a seller</h4>
                        <p class="mt-3"><strong>{{ $vendor->shop_name }}</strong> is approved. You can still shop with the same account.</p>
                        <a class="common_btn mt-3" href="{{ route('vendor.dashboard') }}">Go to seller dashboard</a>
                    </div>
                </div>
                @break

            @case(\App\Enums\VendorStatus::Suspended)
                <div class="wsus__dashboard">
                    <div class="wsus__message text-center py-5">
                        <h4 class="text-warning"><i class="far fa-ban"></i> Shop suspended</h4>
                        <p class="mt-3"><strong>{{ $vendor->shop_name }}</strong> is currently suspended, so the seller dashboard is unavailable.</p>
                        @if ($vendor->statusReason())
                            <p class="mt-2"><strong>Reason:</strong> {{ $vendor->statusReason() }}</p>
                        @endif
                        <p class="mt-2">Contact support if you think this is a mistake. You can still shop normally.</p>
                    </div>
                </div>
                @break
        @endswitch
    @endif
@endsection
