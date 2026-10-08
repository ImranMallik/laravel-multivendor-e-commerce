<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('admin.layouts.partials.head')
</head>

<body>
    <div id="app">
        <section class="section">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                        <div class="login-brand">
                            <img src="{{ asset('admin-assets/img/stisla-fill.svg') }}" alt="logo" width="100" class="shadow-light rounded-circle">
                        </div>

                        @yield('content')

                        <div class="simple-footer">
                            Copyright &copy; {{ date('Y') }} {{ config('app.name') }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @include('admin.layouts.partials.scripts')
    @include('admin.layouts.partials.alerts')
</body>
</html>
