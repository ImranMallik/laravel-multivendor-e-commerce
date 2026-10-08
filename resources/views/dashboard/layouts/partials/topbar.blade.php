@php($currentUser = auth('web')->user())
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-3 border-bottom">
    <h4 class="mb-0 text-capitalize">@yield('page-title', 'Dashboard')</h4>
    <div class="d-flex align-items-center gap-3">
        @if ($currentUser->isVendor() && $currentUser->vendor)
            <img src="{{ $currentUser->vendor->logo_url }}" alt="{{ $currentUser->vendor->shop_name }}"
                 class="rounded-circle avatar-cover" width="40" height="40">
            <span class="fw-semibold">{{ $currentUser->vendor->shop_name }}</span>
            <span class="text-muted">&middot;</span>
        @endif
        <img src="{{ $currentUser->avatar_url }}" alt="{{ $currentUser->name }}"
             class="rounded-circle avatar-cover" width="40" height="40">
        <span>{{ $currentUser->name }}</span>
    </div>
</div>
