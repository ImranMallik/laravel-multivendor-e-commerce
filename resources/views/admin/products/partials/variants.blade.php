{{--
    Variants card of the admin product form. Expects (from ProductFormComposer): $variantAttributes, $variantRows,
    $variantDefault; optional $product. Behaviour: public/shared-assets/js/variants.js (data-* hooks only).
    Rows are posted as variants[i][...]; the default radio posts variant_default = the row key.
--}}
@php
    $product ??= null;
    $variantsOn = (bool) old('has_variants', $product?->has_variants ?? false);
    $nextIndex = $variantRows === [] ? 0 : max(array_map('intval', array_keys($variantRows))) + 1;
@endphp

<x-admin.card title="Variants" data-variants-root data-next-index="{{ $nextIndex }}">
    <x-admin.switch name="has_variants" label="This product has variants (size, color, ...)" :checked="$variantsOn" data-has-variants />

    <div data-variants-section>
        <p class="text-muted mt-2">Each variant has its own SKU, price, stock and image. Prices and stock are set per variant.</p>
        @error('variants')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        @error('variant_default')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        @if ($variantAttributes->isEmpty())
            <div class="alert alert-light">
                There are no active attributes yet. <a href="{{ route('admin.attributes.create') }}">Create an attribute</a> (for example Size or Color) first.
            </div>
        @else
            <h6>1. Choose attributes and values</h6>
            @foreach ($variantAttributes as $attribute)
                <div class="mb-2">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="attribute-{{ $attribute->id }}"
                               data-attribute-choice data-attribute-id="{{ $attribute->id }}" data-attribute-name="{{ $attribute->name }}">
                        <label class="custom-control-label" for="attribute-{{ $attribute->id }}"><strong>{{ $attribute->name }}</strong></label>
                    </div>
                    <div class="ml-4 mt-1" data-attribute-values="{{ $attribute->id }}" hidden>
                        @foreach ($attribute->activeValues as $value)
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" class="custom-control-input" id="value-{{ $value->id }}"
                                       data-value-choice data-attribute-id="{{ $attribute->id }}" data-value-id="{{ $value->id }}" data-value-label="{{ $value->value }}">
                                <label class="custom-control-label" for="value-{{ $value->id }}">{{ $value->value }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <x-admin.button variant="primary" icon="fas fa-magic" data-variants-generate>Generate variants</x-admin.button>
        @endif

        <div class="alert alert-info mt-3" data-variants-message role="status" hidden></div>

        <h6 class="mt-4">2. Fill in the variants</h6>
        <div class="form-row align-items-end mb-2">
            <div class="col-md-3"><label class="small" for="apply-price">Price</label><input type="number" step="0.01" min="0" id="apply-price" class="form-control form-control-sm" data-apply-value="price"></div>
            <div class="col-md-3"><label class="small" for="apply-offer">Offer price</label><input type="number" step="0.01" min="0" id="apply-offer" class="form-control form-control-sm" data-apply-value="offer_price"></div>
            <div class="col-md-3"><label class="small" for="apply-stock">Stock</label><input type="number" min="0" id="apply-stock" class="form-control form-control-sm" data-apply-value="stock_qty"></div>
            <div class="col-md-3 text-nowrap">
                <button type="button" class="btn btn-sm btn-light" data-apply-all="price">Price to all</button>
                <button type="button" class="btn btn-sm btn-light" data-apply-all="offer_price">Offer to all</button>
                <button type="button" class="btn btn-sm btn-light" data-apply-all="stock_qty">Stock to all</button>
            </div>
        </div>
        <small class="form-text text-muted mb-2">The offer dates (in the Pricing card) apply to every variant offer price. Values entered here are also used for new variants you generate.</small>

        <div class="table-responsive">
            <table class="table table-sm table-striped">
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
                        @include('admin.products.partials.variant-row', ['index' => $index, 'row' => $row, 'default' => (string) $index === (string) $variantDefault])
                    @endforeach
                </tbody>
            </table>
        </div>
        <template data-variant-template>
            @include('admin.products.partials.variant-row', ['index' => '__INDEX__', 'row' => null, 'default' => false])
        </template>
    </div>
</x-admin.card>
