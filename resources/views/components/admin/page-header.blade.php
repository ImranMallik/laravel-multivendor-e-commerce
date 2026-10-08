{{--
    Admin template section header: title, action buttons (slot) and breadcrumb.
    Breadcrumb defaults to "Dashboard / {title}". Pass :breadcrumbs="['Vendors' => route(...)]" to add parents.
--}}
@props(['title', 'breadcrumbs' => null])

@php
    $crumbs = $breadcrumbs ?? ($title === 'Dashboard' ? [] : ['Dashboard' => route('admin.dashboard')]);
@endphp

<div class="section-header">
    <h1>{{ $title }}</h1>

    @if (trim((string) $slot) !== '')
        <div class="section-header-button">{{ $slot }}</div>
    @endif

    <div class="section-header-breadcrumb">
        @foreach ($crumbs as $label => $url)
            <div class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></div>
        @endforeach
        <div class="breadcrumb-item active">{{ $title }}</div>
    </div>
</div>
