@php($currentAdmin = auth('admin')->user())
<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar">
    <ul class="navbar-nav mr-auto">
        <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
    </ul>
    <ul class="navbar-nav navbar-right">
        <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <img alt="avatar" src="{{ $currentAdmin->photo_url }}" class="rounded-circle mr-1 admin-img-cover">
                <div class="d-sm-none d-lg-inline-block">{{ $currentAdmin->name }}</div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">{{ $currentAdmin->email }}</div>
                <a href="{{ route('admin.profile.edit') }}" class="dropdown-item has-icon">
                    <i class="far fa-user"></i> My Profile
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item has-icon text-danger"
                   data-confirm="You will need to sign in again to access the admin area."
                   data-confirm-title="Log out?"
                   data-confirm-button="Yes, log out"
                   data-confirm-icon="question"
                   data-confirm-form="admin-logout-form">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <form id="admin-logout-form" method="POST" action="{{ route('admin.logout') }}" class="d-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</nav>
