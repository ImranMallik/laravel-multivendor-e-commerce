{{--
    Admin template select. :options is [value => label].
    <x-admin.form.select name="status" label="Status" :options="$options" placeholder="All statuses" :selected="request('status')" />
--}}
@props(['name', 'options', 'label' => null, 'selected' => null, 'placeholder' => null, 'help' => null, 'errorBag' => 'default'])

@php
    $invalid = $errors->{$errorBag}->has($name);
    $current = (string) old($name, $selected);
@endphp

<div class="form-group">
    @if ($label)
        <label for="{{ $attributes->get('id', $name) }}">{{ $label }}</label>
    @endif

    <select name="{{ $name }}" {{ $attributes->merge(['id' => $name])->class(['form-control', 'is-invalid' => $invalid]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $value === $current)>{{ $text }}</option>
        @endforeach
    </select>

    @if ($help)<small class="form-text text-muted">{{ $help }}</small>@endif
    @if ($invalid)<div class="invalid-feedback">{{ $errors->{$errorBag}->first($name) }}</div>@endif
</div>
