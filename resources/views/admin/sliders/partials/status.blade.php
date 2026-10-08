<x-admin.switch :checked="$slider->is_active"
                data-slider-toggle
                data-url="{{ route('admin.sliders.status', $slider) }}"
                aria-label="Active: {{ $slider->title }}" />
