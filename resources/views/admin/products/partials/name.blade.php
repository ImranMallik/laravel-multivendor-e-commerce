<a href="{{ route('admin.products.show', $product) }}">{{ $product->name }}</a>
<div class="text-muted small">SKU: {{ $product->sku }}</div>
@if ($product->has_variants)
    <div class="small"><x-admin.badge color="info">{{ $product->sellableVariants()->count() }} {{ \Illuminate\Support\Str::plural('variant', $product->sellableVariants()->count()) }}</x-admin.badge></div>
@endif
