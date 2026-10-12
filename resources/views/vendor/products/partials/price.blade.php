@if ($product->has_variants)
    {{ $product->priceRangeLabel() }}
@elseif ($product->hasValidOffer())
    <span class="fw-semibold">{{ \App\Models\Product::money((float) $product->offer_price) }}</span>
    <del class="text-muted small d-block">{{ \App\Models\Product::money((float) $product->price) }}</del>
@else
    {{ \App\Models\Product::money((float) $product->price) }}
@endif
