{{--
    Admin template card.
    <x-admin.card title="Edit Profile" variant="primary">
        body
        <x-slot:actions> header buttons </x-slot:actions>
        <x-slot:footer> footer content </x-slot:footer>
    </x-admin.card>
--}}
@props(['title' => null, 'variant' => null, 'flush' => false])

<div {{ $attributes->class(['card', "card-{$variant}" => $variant]) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            @if ($title)<h4>{{ $title }}</h4>@endif
            @isset($actions)
                <div class="card-header-action">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['card-body', 'p-0' => $flush])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
