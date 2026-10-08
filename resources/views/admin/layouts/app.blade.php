<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('admin.layouts.partials.head')
</head>

<body>
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            @include('admin.layouts.partials.navbar')
            @include('admin.layouts.partials.sidebar')

            <div class="main-content">
                <section class="section">
                    <x-admin.page-header :title="trim($__env->yieldContent('page-title'))">
                        @yield('page-actions')
                    </x-admin.page-header>

                    <div class="section-body">
                        @yield('content')
                    </div>
                </section>
            </div>

            @include('admin.layouts.partials.footer')
        </div>
    </div>

    @include('admin.layouts.partials.scripts')
    @include('admin.layouts.partials.alerts')
</body>
</html>
