{{--
    No search results, in the same template box as <x-frontend.empty-state> (cart_empty.html "wsus__cart_list cart_empty").
    Expects: $term, $popularCategories (array of name + url). TODO(flash sale): link it here once that module exists.
--}}
<div class="wsus__cart_list cart_empty p-3 p-sm-5 text-center">
    <p class="mb-3">No results for “{{ $term }}”.</p>
    <ul class="list-unstyled mb-4">
        <li>Check the spelling, or try fewer or more general words.</li>
        <li>Search by product name, brand, category or SKU.</li>
    </ul>
    @if ($popularCategories)
        <p class="mb-3">Popular categories</p>
        <ul class="list-unstyled d-flex flex-wrap justify-content-center gap-3 mb-0">
            @foreach ($popularCategories as $popular)
                <li><a class="common_btn" href="{{ $popular['url'] }}">{{ $popular['name'] }}</a></li>
            @endforeach
        </ul>
    @else
        <a href="{{ route('shop.index') }}" class="common_btn"><i class="fal fa-store me-2"></i>all products</a>
    @endif
</div>
