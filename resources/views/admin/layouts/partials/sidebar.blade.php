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
            <li class="dropdown {{ request()->routeIs('admin.categories.*', 'admin.sub-categories.*', 'admin.child-categories.*') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown"><i class="fas fa-th-list"></i> <span>Categories</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.categories.index') }}">Category</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.sub-categories.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.sub-categories.index') }}">Sub Category</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.child-categories.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.child-categories.index') }}">Child Category</a>
                    </li>
                </ul>
            </li>
            <li class="{{ request()->routeIs('admin.sliders.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.sliders.index') }}">
                    <i class="fas fa-images"></i> <span>Sliders</span>
                </a>
            </li>
        </ul>
    </aside>
</div>
