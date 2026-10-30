{{--
    The template's shop topbar (product_grid_view.html "wsus__product_topbar") with the sort select only; like the
    template it is hidden below 768px. GET form: the other filters ride along as hidden fields; search.js submits
    it when the select changes. Expects: $sort, $sortOptions, $keep.
--}}
<div class="row">
    <div class="col-xl-12 d-none d-md-block mb-4">
        <div class="wsus__product_topbar">
            <div class="wsus__product_topbar_left">
                <form method="GET" action="{{ route('shop.index') }}" data-shop-sort>
                    @foreach ($keep as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <div class="wsus__topbar_select">
                        <select class="select_2" name="sort" aria-label="Sort products">
                            @foreach ($sortOptions as $option)
                                <option value="{{ $option->value }}" @selected($option === $sort)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="common_btn ms-2">sort</button></noscript>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
