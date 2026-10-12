{{-- Product grid with pagination. Expects: $products (paginator), $empty (message). --}}
@if ($products->isNotEmpty())
    <div class="row">
        @foreach ($products as $product)
            <div class="col-xl-3 col-sm-6 col-lg-4">
                <x-frontend.product-card :product="$product" />
            </div>
        @endforeach
    </div>
    <x-frontend.pagination :paginator="$products" />
@else
    <x-frontend.empty-state :message="$empty" link-text="back to home" :link-url="route('home')" />
@endif
