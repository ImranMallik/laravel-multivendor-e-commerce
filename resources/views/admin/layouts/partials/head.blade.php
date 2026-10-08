<meta charset="UTF-8">
<meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') &mdash; {{ config('app.name') }}</title>

<!-- General CSS Files -->
<link rel="stylesheet" href="{{ asset('admin-assets/modules/bootstrap/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/modules/fontawesome/css/all.min.css') }}">

<!-- DataTables (the template's own bundle + Bootstrap 4 skin), loaded once for every admin page -->
<link rel="stylesheet" href="{{ asset('admin-assets/modules/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css') }}">

<!-- Template CSS -->
<link rel="stylesheet" href="{{ asset('admin-assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/css/components.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/css/custom.css') }}">

@stack('styles')
