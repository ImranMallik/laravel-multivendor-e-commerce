<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('dashboard.layouts.partials.head')
</head>

<body>
    <section id="wsus__dashboard">
        <div class="container-fluid">
            {{-- One sidebar partial per role: dashboard/sidebars/customer.blade.php or vendor.blade.php --}}
            @include('dashboard.sidebars.'.auth('web')->user()->role->value)

            <div class="row">
                <div class="col-xl-9 col-xxl-10 col-lg-9 ms-auto">
                    <div class="dashboard_content">
                        @include('dashboard.layouts.partials.topbar')

                        @yield('content')

                        @include('dashboard.layouts.partials.footer')
                    </div>
                </div>
            </div>
        </div>
    </section>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>

    @include('dashboard.layouts.partials.scripts')
    @include('frontend.layouts.flash')
</body>
</html>
