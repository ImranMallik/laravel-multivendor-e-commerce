{{--
    Variants section of the vendor product form (dashboard styling). Expects (from ProductFormComposer):
    $variantAttributes, $variantRows, $variantDefault; optional $product, $reviewRequired.
    Behaviour: public/shared-assets/js/variants.js (data-* hooks only), same as the admin form.
--}}
@php
    $product ??= null;
    $variantsOn = (bool) old('has_variants', $product?->has_variants ?? false);
    $nextIndex = $variantRows === [] ? 0 : max(array_map('intval', array_keys($variantRows))) + 1;
@endphp

<div data-variants-root data-next-index="{{ $nextIndex }}">
    <h4>variants</h4>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="has_variants" name="has_variants" value="1" data-has-variants @checked($variantsOn)>
        <label class="form-check-label" for="has_variants">This product has variants (size, color, ...)</label>
    </div>

    <div data-variants-section class="mb-4">
        <p class="text-muted small">Each variant has its own SKU, price, stock and image. Prices and stock are set per variant.</p>
        @error('variants')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        @error('variant_default')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        @if ($variantAttributes->isEmpty())
            <div class="alert alert-light">There are no attributes to choose from yet. Ask the store administrator to add attributes such as Size or Color.</div>
        @else
            <h6>1. Choose attributes and values</h6>
            @foreach ($variantAttributes as $attribute)
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="attribute-{{ $attribute->id }}"
                               data-attribute-choice data-attribute-id="{{ $attribute->id }}" data-attribute-name="{{ $attribute->name }}">
                        <label class="form-check-label fw-semibold" for="attribute-{{ $attribute->id }}">{{ $attribute->name }}</label>
                    </div>
                    <div class="ms-4 mt-1" data-attribute-values="{{ $attribute->id }}" hidden>
                        @foreach ($attribute->activeValues as $value)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="value-{{ $value->id }}"
                                       data-value-choice data-attribute-id="{{ $attribute->id }}" data-value-id="{{ $value->id }}" data-value-label="{{ $value->value }}">
                                <label class="form-check-label" for="value-{{ $value->id }}">{{ $value->value }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <button type="button" class="btn btn-primary btn-sm" data-variants-generate><i class="far fa-magic"></i> Generate variants</button>
        @endif

        <div class="alert alert-info mt-3" data-variants-message role="status" hidden></div>

        <h6 class="mt-4">2. Fill in the variants</h6>
        <div class="row g-2 align-items-end mb-2">
            <div class="col-md-3"><label class="form-label small" for="apply-price">Price</label><input type="number" step="0.01" min="0" id="apply-price" class="form-control form-control-sm" data-apply-value="price"></div>
            <div class="col-md-3"><label class="form-label small" for="apply-offer">Offer price</label><input type="number" step="0.01" min="0" id="apply-offer" class="form-control form-control-sm" data-apply-value="offer_price"></div>
            <div class="col-md-3"><label class="form-label small" for="apply-stock">Stock</label><input type="number" min="0" id="apply-stock" class="form-control form-control-sm" data-apply-value="stock_qty"></div>
            <div class="col-md-3 text-nowrap">
                <button type="button" class="btn btn-sm btn-light" data-apply-all="price">Price to all</button>
                <button type="button" class="btn btn-sm btn-light" data-apply-all="offer_price">Offer to all</button>
                <button type="button" class="btn btn-sm btn-light" data-apply-all="stock_qty">Stock to all</button>
            </div>
        </div>
        <div class="form-text mb-2">The offer dates above apply to every variant offer price. Values entered here are also used for new variants you generate.</div>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Variant</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Offer price</th>
                        <th>Stock</th>
                        <th>Image</th>
                        <th>Active</th>
                        <th>Default</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-variant-rows>
                    @foreach ($variantRows as $index => $row)
                        @include('vendor.products.partials.variant-row', ['index' => $index, 'row' => $row, 'default' => (string) $index === (string) $variantDefault])
                    @endforeach
                </tbody>
            </table>
        </div>
        <template data-variant-template>
            @include('vendor.products.partials.variant-row', ['index' => '__INDEX__', 'row' => null, 'default' => false])
        </template>
    </div>
</div>
