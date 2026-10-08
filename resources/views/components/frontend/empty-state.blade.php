@props(['message', 'linkText' => null, 'linkUrl' => null, 'icon' => 'fal fa-store'])

<div class="wsus__cart_list cart_empty p-3 p-sm-5 text-center">
    <p class="mb-4">{{ $message }}</p>
    @if ($linkText && $linkUrl)
        <a href="{{ $linkUrl }}" class="common_btn"><i class="{{ $icon }} me-2"></i>{{ $linkText }}</a>
    @endif
</div>
