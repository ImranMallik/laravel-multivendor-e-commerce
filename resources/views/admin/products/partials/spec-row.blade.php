{{-- One specification row. Expects: $index (number, or __INDEX__ inside the JS template), $row [key, value]. --}}
<div class="form-row align-items-start" data-spec-row>
    <div class="col-5 form-group">
        <input type="text" name="specifications[{{ $index }}][key]" value="{{ $row['key'] ?? '' }}" maxlength="100"
               placeholder="Name, e.g. Material" aria-label="Specification name"
               class="form-control {{ $errors->has("specifications.$index.key") ? 'is-invalid' : '' }}">
        @if ($errors->has("specifications.$index.key"))<div class="invalid-feedback">{{ $errors->first("specifications.$index.key") }}</div>@endif
    </div>
    <div class="col-6 form-group">
        <input type="text" name="specifications[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}" maxlength="500"
               placeholder="Value, e.g. 100% cotton" aria-label="Specification value"
               class="form-control {{ $errors->has("specifications.$index.value") ? 'is-invalid' : '' }}">
        @if ($errors->has("specifications.$index.value"))<div class="invalid-feedback">{{ $errors->first("specifications.$index.value") }}</div>@endif
    </div>
    <div class="col-1">
        <button type="button" class="btn btn-icon btn-light" data-spec-remove aria-label="Remove specification"><i class="fas fa-times"></i></button>
    </div>
</div>
