<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') &mdash; {{ config('app.name') }}</title>
<link rel="icon" type="image/png" href="{{ asset('frontend-assets/images/favicon.png') }}">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/slick.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/jquery.nice-number.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/jquery.calendar.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/add_row_custon.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/mobile_menu.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/jquery.exzoom.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/multiple-image-video.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/ranger_style.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/jquery.classycountdown.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/venobox.min.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/responsive.css') }}">
<link rel="stylesheet" href="{{ asset('frontend-assets/css/dashboard.css') }}">
@stack('styles')
