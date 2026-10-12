{{-- The values of an attribute as small badges (colors show their swatch). --}}
@foreach ($attribute->values as $value)
    <span class="badge {{ $value->is_active ? 'badge-light' : 'badge-secondary' }} mr-1" title="{{ $value->is_active ? 'Active' : 'Inactive' }}">
        @if ($value->color_code)<i class="fas fa-circle" data-swatch="{{ $value->color_code }}"></i>@endif
        {{ $value->value }}
    </span>
@endforeach
