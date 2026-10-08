{{--
    Admin template text-like input (form-group > form-control) with the template's invalid state.
    <x-admin.form.input name="email" label="Email" type="email" :value="$admin->email" required />
    Pass error-bag="updatePassword" when the form validates into a named error bag.
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'errorBag' => 'default'])

@php($invalid = $errors->{$errorBag}->has($name))

<div class="form-group">
    @if ($label)
        <label for="{{ $attributes->get('id', $name) }}">{{ $label }}</label>
    @endif

    <input
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        {{ $attributes->merge(['id' => $name])->class(['form-control', 'is-invalid' => $invalid]) }}
    >

    @if ($help)<small class="form-text text-muted">{{ $help }}</small>@endif
    @if ($invalid)<div class="invalid-feedback">{{ $errors->{$errorBag}->first($name) }}</div>@endif
</div>
