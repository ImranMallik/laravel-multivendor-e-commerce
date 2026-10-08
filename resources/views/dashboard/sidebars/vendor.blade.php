<div class="dashboard_sidebar">
    <span class="close_icon">
        <i class="far fa-bars dash_bar"></i>
        <i class="far fa-times dash_close"></i>
    </span>
    <a href="{{ route('vendor.dashboard') }}" class="dash_logo"><img src="{{ asset('frontend-assets/images/logo.png') }}" alt="{{ config('app.name') }}" class="img-fluid"></a>
    <ul class="dashboard_link">
        @if (auth('web')->user()->isApprovedVendor())
            <li><a class="{{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}"><i class="fas fa-tachometer"></i>Dashboard</a></li>
            {{-- TODO: these four open "coming soon" pages until the modules exist. --}}
            <li><a class="{{ request()->routeIs('vendor.products') ? 'active' : '' }}" href="{{ route('vendor.products') }}"><i class="far fa-box"></i> Products</a></li>
            <li><a class="{{ request()->routeIs('vendor.orders') ? 'active' : '' }}" href="{{ route('vendor.orders') }}"><i class="fas fa-list-ul"></i> Orders</a></li>
            <li><a class="{{ request()->routeIs('vendor.earnings') ? 'active' : '' }}" href="{{ route('vendor.earnings') }}"><i class="far fa-dollar-sign"></i> Earnings</a></li>
            <li><a class="{{ request()->routeIs('vendor.withdrawals') ? 'active' : '' }}" href="{{ route('vendor.withdrawals') }}"><i class="far fa-money-bill-wave"></i> Withdrawals</a></li>
            <li><a class="{{ request()->routeIs('vendor.shop.*') ? 'active' : '' }}" href="{{ route('vendor.shop.edit') }}"><i class="far fa-store"></i> Shop Settings</a></li>
            <li><a class="{{ request()->routeIs('vendor.profile.*') ? 'active' : '' }}" href="{{ route('vendor.profile.edit') }}"><i class="far fa-user"></i> My Profile</a></li>
        @else
            <li><a class="{{ request()->routeIs('vendor.status') ? 'active' : '' }}" href="{{ route('vendor.status') }}"><i class="far fa-hourglass"></i> Application status</a></li>
        @endif
        <li><a href="{{ route('home') }}"><i class="far fa-globe"></i> View storefront</a></li>
        @include('dashboard.partials.logout-link')
    </ul>
</div>
