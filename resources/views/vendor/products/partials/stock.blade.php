@if ($product->in_stock)
    {{ $product->total_stock }}
@else
    <span class="badge bg-danger">Out of stock</span>
@endif
