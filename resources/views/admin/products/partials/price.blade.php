{{-- Regular price; a valid offer is shown as the price with the regular one struck through. Variants: the price range. --}}
@if ($product->has_variants)
    {{ $product->priceRangeLabel() }}
@elseif ($product->hasValidOffer())
    <span class="text-success font-weight-bold">{{ number_format((float) $product->offer_price, 2) }}</span>
    <del class="text-muted small d-block">{{ number_format((float) $product->price, 2) }}</del>
@else
    {{ number_format((float) $product->price, 2) }}
    @if ($product->offer_price !== null)
        <div class="text-muted small">Offer {{ number_format((float) $product->offer_price, 2) }} (not running)</div>
    @endif
@endif
