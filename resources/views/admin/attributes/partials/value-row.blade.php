{{-- One value row. Expects: $index (number, or __INDEX__ inside the JS template), $row, $isColor. --}}
<div class="form-row align-items-start" data-value-row>
    <input type="hidden" name="values[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
    <div class="col-5 form-group">
        <input type="text" name="values[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}" maxlength="100"
               placeholder="Value, e.g. M or Red" aria-label="Value" class="form-control {{ $errors->has("values.$index.value") ? 'is-invalid' : '' }}">
        @if ($errors->has("values.$index.value"))<div class="invalid-feedback">{{ $errors->first("values.$index.value") }}</div>@endif
    </div>
    <div class="col-2 form-group {{ $isColor ? '' : 'd-none' }}" data-color-cell>
        <input type="color" name="values[{{ $index }}][color_code]" value="{{ $row['color_code'] ?: '#000000' }}" aria-label="Color"
               class="form-control p-1 {{ $errors->has("values.$index.color_code") ? 'is-invalid' : '' }}" @disabled(! $isColor)>
        @if ($errors->has("values.$index.color_code"))<div class="invalid-feedback">{{ $errors->first("values.$index.color_code") }}</div>@endif
    </div>
    <div class="col-2">
        <x-admin.switch name="values[{{ $index }}][is_active]" label="Active" :checked="(bool) ($row['is_active'] ?? true)" />
    </div>
    <div class="col-3 text-nowrap">
        <button type="button" class="btn btn-icon btn-light" data-value-up aria-label="Move up"><i class="fas fa-arrow-up"></i></button>
        <button type="button" class="btn btn-icon btn-light" data-value-down aria-label="Move down"><i class="fas fa-arrow-down"></i></button>
        <button type="button" class="btn btn-icon btn-danger" data-value-remove aria-label="Remove value"><i class="fas fa-times"></i></button>
    </div>
</div>
