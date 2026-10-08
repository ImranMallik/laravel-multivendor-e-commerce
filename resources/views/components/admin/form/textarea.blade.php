{{--
    Admin template textarea.
    <x-admin.form.textarea name="description" label="Description" :value="$vendor->description" rows="4" />
--}}
@props(['name', 'label' => null, 'value' => null, 'help' => null, 'errorBag' => 'default'])

@php($invalid = $errors->{$errorBag}->has($name))

<div class="form-group">
    @if ($label)
        <label for="{{ $attributes->get('id', $name) }}">{{ $label }}</label>
    @endif

    <textarea name="{{ $name }}" {{ $attributes->merge(['id' => $name, 'rows' => 4])->class(['form-control', 'is-invalid' => $invalid]) }}>{{ old($name, $value) }}</textarea>

    @if ($help)<small class="form-text text-muted">{{ $help }}</small>@endif
    @if ($invalid)<div class="invalid-feedback">{{ $errors->{$errorBag}->first($name) }}</div>@endif
</div>
