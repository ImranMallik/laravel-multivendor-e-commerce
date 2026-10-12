{{--
    One variant row of the vendor product form. Expects: $index (number, or __INDEX__ inside the JS template),
    $row (array from ProductVariantFormData, or null for the template), $default (bool: the default radio is on).
--}}
@php
    $row ??= null;
    $values = $row['values'] ?? [];
    $combo = collect($values)->map(fn ($id) => (int) $id)->sort()->implode('-');
    $error = fn (string $field) => $errors->first("variants.$index.$field");
@endphp
<tr data-variant-row data-combo="{{ $combo }}" data-values="{{ json_encode((object) $values) }}">
    <td>
        <span data-variant-label>{{ $row['label'] ?? '' }}</span>
        <span data-variant-values>
            <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
            @foreach ($values as $attributeId => $valueId)
                <input type="hidden" name="variants[{{ $index }}][values][{{ $attributeId }}]" value="{{ $valueId }}">
            @endforeach
        </span>
        @if ($error('values'))<div class="text-danger small">{{ $error('values') }}</div>@endif
        @if ($error('id'))<div class="text-danger small">{{ $error('id') }}</div>@endif
    </td>
    <td>
        <input type="text" name="variants[{{ $index }}][sku]" value="{{ $row['sku'] ?? '' }}" maxlength="100" aria-label="Variant SKU" data-variant-sku
               class="form-control form-control-sm {{ $error('sku') ? 'is-invalid' : '' }}">
        @if ($error('sku'))<div class="invalid-feedback">{{ $error('sku') }}</div>@endif
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="variants[{{ $index }}][price]" value="{{ $row['price'] ?? '' }}" aria-label="Variant price" data-variant-price
               class="form-control form-control-sm {{ $error('price') ? 'is-invalid' : '' }}">
        @if ($error('price'))<div class="invalid-feedback">{{ $error('price') }}</div>@endif
    </td>
    <td>
        <input type="number" step="0.01" min="0" name="variants[{{ $index }}][offer_price]" value="{{ $row['offer_price'] ?? '' }}" aria-label="Variant offer price" data-variant-offer
               class="form-control form-control-sm {{ $error('offer_price') ? 'is-invalid' : '' }}">
        @if ($error('offer_price'))<div class="invalid-feedback">{{ $error('offer_price') }}</div>@endif
    </td>
    <td>
        <input type="number" min="0" name="variants[{{ $index }}][stock_qty]" value="{{ $row['stock_qty'] ?? 0 }}" aria-label="Variant stock" data-variant-stock
               class="form-control form-control-sm {{ $error('stock_qty') ? 'is-invalid' : '' }}">
        @if ($error('stock_qty'))<div class="invalid-feedback">{{ $error('stock_qty') }}</div>@endif
    </td>
    <td>
        <img src="{{ $row['image_url'] ?? '' }}" alt="Variant image" width="40" height="40" class="rounded avatar-cover mb-1" data-variant-preview @if (empty($row['image_url'])) hidden @endif>
        <input type="file" name="variants[{{ $index }}][image]" accept=".jpg,.jpeg,.png,.webp" aria-label="Variant image" data-variant-image class="form-control form-control-sm">
        @if (! empty($row['image_url']))
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="variant-{{ $index }}-remove-image" name="variants[{{ $index }}][remove_image]" value="1">
                <label class="form-check-label small" for="variant-{{ $index }}-remove-image">Remove image</label>
            </div>
        @endif
        @if ($error('image'))<div class="text-danger small">{{ $error('image') }}</div>@endif
    </td>
    <td>
        <input type="hidden" name="variants[{{ $index }}][is_active]" value="0">
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" name="variants[{{ $index }}][is_active]" value="1" aria-label="Variant active" @checked((bool) ($row['is_active'] ?? true))>
        </div>
    </td>
    <td class="text-center"><input type="radio" class="form-check-input" name="variant_default" value="{{ $index }}" aria-label="Default variant" data-variant-default @checked($default)></td>
    <td><button type="button" class="btn btn-sm btn-danger" data-variant-remove aria-label="Remove variant"><i class="far fa-times"></i></button></td>
</tr>
