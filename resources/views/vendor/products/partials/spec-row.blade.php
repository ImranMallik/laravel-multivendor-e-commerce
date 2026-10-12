{{-- One specification row (vendor panel). Expects: $index (number, or __INDEX__ inside the JS template), $row [key, value]. --}}
<div class="row g-2 mb-2" data-spec-row>
    <div class="col-5">
        <input type="text" name="specifications[{{ $index }}][key]" value="{{ $row['key'] ?? '' }}" maxlength="100"
               placeholder="Name, e.g. Material" aria-label="Specification name"
               class="form-control {{ $errors->has("specifications.$index.key") ? 'is-invalid' : '' }}">
        @if ($errors->has("specifications.$index.key"))<div class="invalid-feedback">{{ $errors->first("specifications.$index.key") }}</div>@endif
    </div>
    <div class="col-6">
        <input type="text" name="specifications[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}" maxlength="500"
               placeholder="Value, e.g. 100% cotton" aria-label="Specification value"
               class="form-control {{ $errors->has("specifications.$index.value") ? 'is-invalid' : '' }}">
        @if ($errors->has("specifications.$index.value"))<div class="invalid-feedback">{{ $errors->first("specifications.$index.value") }}</div>@endif
    </div>
    <div class="col-1">
        <button type="button" class="btn btn-light" data-spec-remove aria-label="Remove specification"><i class="far fa-times"></i></button>
    </div>
</div>
