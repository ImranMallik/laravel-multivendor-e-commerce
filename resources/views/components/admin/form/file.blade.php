{{--
    Admin template file input.
    <x-admin.form.file name="photo" label="Profile photo" accept=".jpg,.jpeg,.png,.webp" help="JPG, PNG or WEBP, up to 2 MB." />
--}}
@props(['name', 'label' => null, 'help' => null, 'errorBag' => 'default'])

@php($invalid = $errors->{$errorBag}->has($name))

<div class="form-group">
    @if ($label)
        <label for="{{ $attributes->get('id', $name) }}">{{ $label }}</label>
    @endif

    <input type="file" name="{{ $name }}" {{ $attributes->merge(['id' => $name])->class(['form-control-file', 'is-invalid' => $invalid]) }}>

    @if ($help)<small class="form-text text-muted">{{ $help }}</small>@endif
    @if ($invalid)<div class="invalid-feedback d-block">{{ $errors->{$errorBag}->first($name) }}</div>@endif
</div>
