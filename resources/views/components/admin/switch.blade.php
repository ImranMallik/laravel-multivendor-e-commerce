{{--
    Admin template toggle switch (custom-switch).
    In a form:   <x-admin.switch name="is_active" label="Active" :checked="$slider->is_active" />
                 (sends 0 when off and 1 when on)
    In a table:  <x-admin.switch :checked="$row->is_active" aria-label="Status" data-url="..." />
    Extra attributes (data-*, aria-label) go on the checkbox.
--}}
@props(['name' => null, 'label' => null, 'checked' => false])

<label class="custom-switch mt-2 pl-0">
    @if ($name)
        <input type="hidden" name="{{ $name }}" value="0">
    @endif
    <input type="checkbox" @if ($name) name="{{ $name }}" value="1" @endif
           {{ $attributes->class(['custom-switch-input']) }} @checked($checked)>
    <span class="custom-switch-indicator"></span>
    @if ($label)
        <span class="custom-switch-description">{{ $label }}</span>
    @endif
</label>
