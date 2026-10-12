<a href="{{ route('vendor.products.edit', $product) }}">{{ $product->name }}</a>
<div class="text-muted small">SKU: {{ $product->sku }}</div>
@if ($product->has_variants)
    <span class="badge bg-info">{{ $product->sellableVariants()->count() }} {{ \Illuminate\Support\Str::plural('variant', $product->sellableVariants()->count()) }}</span>
@endif
