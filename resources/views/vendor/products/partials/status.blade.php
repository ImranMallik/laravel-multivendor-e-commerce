{{-- Active / Inactive switch; it works only once the product is approved. --}}
<div class="form-check form-switch mb-0" @if (! $product->isApproved()) title="Available once the product is approved" @endif>
    <input class="form-check-input" type="checkbox" role="switch"
           data-product-status
           data-url="{{ route('vendor.products.status', $product) }}"
           aria-label="Active: {{ $product->name }}"
           @checked($product->status === \App\Enums\ProductStatus::Active)
           @disabled(! $product->isApproved())>
</div>
@if (! $product->isApproved())
    <small class="text-muted">{{ $product->status->label() }}</small>
@endif
