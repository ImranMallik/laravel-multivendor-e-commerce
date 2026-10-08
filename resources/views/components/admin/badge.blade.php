{{--
    Admin template badge (badge badge-*).
    Preferred: <x-admin.badge :status="$vendor->status" />  — any enum with color() and label().
    Manual:    <x-admin.badge color="info">New</x-admin.badge>
--}}
@props(['status' => null, 'color' => 'secondary'])

<span {{ $attributes->class(['badge', 'badge-'.($status ? $status->color() : $color)]) }}>{{ $status ? $status->label() : $slot }}</span>
