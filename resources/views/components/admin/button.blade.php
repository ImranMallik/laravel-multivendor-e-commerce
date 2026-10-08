{{--
    Admin template button (btn btn-*). Renders <a> when href is given, otherwise <button>.
    <x-admin.button variant="success" icon="fas fa-check" tooltip="Approve">Approve</x-admin.button>
    Icon-only: omit the slot. Extra attributes (data-*, id, onclick-free) are passed through.
--}}
@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'size' => null,
    'block' => false,
    'outline' => false,
    'tooltip' => null,
])

@php
    $hasLabel = trim((string) $slot) !== '';
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes
        ->merge($href ? ['href' => $href] : ['type' => $type])
        ->class([
            'btn',
            $outline ? "btn-outline-{$variant}" : "btn-{$variant}",
            "btn-{$size}" => $size,
            'btn-block' => $block,
            'btn-icon' => $icon && ! $hasLabel,
            'icon-left' => $icon && $hasLabel,
        ]) }}
    @if ($tooltip) data-toggle="tooltip" title="{{ $tooltip }}" aria-label="{{ $tooltip }}" @endif
>
    @if ($icon)<i class="{{ $icon }}"></i>{{ $hasLabel ? ' ' : '' }}@endif{{ $slot }}
</{{ $tag }}>
