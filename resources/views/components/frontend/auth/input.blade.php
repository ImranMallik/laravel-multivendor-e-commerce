@props([
    'name',
    'label',
    'icon',
    'type' => 'text',
    'autocomplete' => null,
    'required' => false,
    'disabled' => false,
    'value' => null,
    'placeholder' => null,
])

@php
    $fieldId = 'auth-'.$name;
    $errorId = $fieldId.'-error';
    $isPassword = $type === 'password';
@endphp

<div class="wsus__login_field">
    <div class="wsus__login_input {{ $isPassword ? 'has-eye' : '' }}">
        <i class="{{ $icon }} auth-icon" aria-hidden="true"></i>
        <label for="{{ $fieldId }}" class="visually-hidden">{{ $label }}</label>
        <input
            id="{{ $fieldId }}"
            type="{{ $type }}"
            name="{{ $name }}"
            placeholder="{{ $placeholder ?? $label }}"
            @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @required($required)
            @disabled($disabled)
            @error($name) class="is-invalid" aria-invalid="true" aria-describedby="{{ $errorId }}" @enderror
        >
        @if ($isPassword)
            <button type="button" class="auth-eye" aria-label="Show password" aria-pressed="false" aria-controls="{{ $fieldId }}">
                <i class="far fa-eye" aria-hidden="true"></i>
            </button>
        @endif
    </div>
    @error($name)
        <p class="auth-error" id="{{ $errorId }}" role="alert">{{ $message }}</p>
    @enderror
</div>
