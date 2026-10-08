<div class="dashboard_sidebar">
    <span class="close_icon">
        <i class="far fa-bars dash_bar"></i>
        <i class="far fa-times dash_close"></i>
    </span>
    <a href="{{ route('home') }}" class="dash_logo"><img src="{{ asset('frontend-assets/images/logo.png') }}" alt="{{ config('app.name') }}" class="img-fluid"></a>
    <ul class="dashboard_link">
        <li><a class="{{ request()->routeIs('account.dashboard') ? 'active' : '' }}" href="{{ route('account.dashboard') }}"><i class="fas fa-tachometer"></i>Dashboard</a></li>
        <li><a class="{{ request()->routeIs('account.orders') ? 'active' : '' }}" href="{{ route('account.orders') }}"><i class="fas fa-list-ul"></i> Orders</a></li>
        <li><a class="{{ request()->routeIs('account.wishlist') ? 'active' : '' }}" href="{{ route('account.wishlist') }}"><i class="far fa-heart"></i> Wishlist</a></li>
        <li><a class="{{ request()->routeIs('account.profile.*') ? 'active' : '' }}" href="{{ route('account.profile.edit') }}"><i class="far fa-user"></i> My Profile</a></li>
        <li><a href="{{ route('home') }}"><i class="far fa-store"></i> Back to shop</a></li>
        @include('dashboard.partials.logout-link')
    </ul>
</div>
