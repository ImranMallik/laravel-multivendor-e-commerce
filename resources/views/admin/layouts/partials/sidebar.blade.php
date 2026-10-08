<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}">{{ config('app.name') }}</a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a
                href="{{ route('admin.dashboard') }}">{{ \Illuminate\Support\Str::of(config('app.name'))->substr(0, 2) }}</a>
        </div>
        <ul class="sidebar-menu">
            {{-- <li class="menu-header">Main</li> --}}
            <li class="{{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-fire"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.vendors*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.vendors.index') }}">
                    <i class="fas fa-store"></i> <span>Vendors</span>
                </a>
            </li>
            <li class="{{ request()->routeIs('admin.sliders.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.sliders.index') }}">
                    <i class="fas fa-images"></i> <span>Sliders</span>
                </a>
            </li>
        </ul>
    </aside>
</div>
