{{--
    Admin template alert for static, in-page notices (flash messages use SweetAlert, not this).
    <x-admin.alert variant="info" title="Heads up" icon="far fa-lightbulb">Text</x-admin.alert>
--}}
@props(['variant' => 'info', 'title' => null, 'icon' => null, 'dismissible' => false])

<div {{ $attributes->class(['alert', "alert-{$variant}", 'alert-has-icon' => $icon, 'alert-dismissible show fade' => $dismissible]) }} role="alert">
    @if ($icon)
        <div class="alert-icon"><i class="{{ $icon }}"></i></div>
    @endif
    <div class="alert-body">
        @if ($dismissible)
            <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        @endif
        @if ($title)<div class="alert-title">{{ $title }}</div>@endif
        {{ $slot }}
    </div>
</div>
