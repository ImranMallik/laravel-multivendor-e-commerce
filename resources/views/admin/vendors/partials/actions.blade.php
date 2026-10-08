{{--
    Row/detail action buttons. Which actions show comes from VendorStatus::availableActions();
    text, icon, colour and confirm copy come from VendorAction. The JS (vendor-actions.js) only reads data-*.
    Expects: $vendor, optional $view (show the View button, default true), optional $size.
--}}
@php
    $view ??= true;
    $size ??= 'sm';
@endphp

@if ($view)
    <x-admin.button :href="route('admin.vendors.show', $vendor)" variant="primary" icon="fas fa-eye" :size="$size" tooltip="View" />
@endif

@foreach ($vendor->status->availableActions() as $action)
    <x-admin.button
        :variant="$action->variant()"
        :icon="$action->icon()"
        :size="$size"
        :tooltip="$action->label()"
        data-vendor-action="{{ $action->value }}"
        data-url="{{ route($action->routeName(), $vendor) }}"
        data-requires-reason="{{ $action->requiresReason() ? '1' : '0' }}"
        data-confirm-title="{{ $action->confirmTitle() }}"
        data-confirm-text="{{ $action->confirmText() }}"
        data-confirm-button="{{ $action->confirmButton() }}"
        data-confirm-icon="{{ $action->confirmIcon() }}"
        data-confirm-color="{{ $action->confirmColor() }}"
    />
@endforeach
