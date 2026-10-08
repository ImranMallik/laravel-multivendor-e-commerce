<x-admin.button :href="route('admin.sliders.edit', $slider)" variant="primary" icon="fas fa-edit" size="sm" tooltip="Edit" />
<x-admin.button variant="danger" icon="fas fa-trash" size="sm" tooltip="Delete"
                data-slider-delete
                data-url="{{ route('admin.sliders.destroy', $slider) }}"
                data-title="{{ $slider->title }}" />
