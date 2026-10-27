{{--
    The ONE quick view modal of the storefront (template "product_popup_modal"), included once in the master
    layout. public/frontend-assets/js/quick-view.js opens it with the skeleton below, loads the product body
    from data-url (route frontend.products.quick-view) and swaps it in. Nothing here is rendered per product.
--}}
<section class="product_popup_modal">
    <div class="modal fade" id="quick-view-modal" tabindex="-1" aria-hidden="true" aria-label="Quick view"
         data-url="{{ route('frontend.products.quick-view', ['slug' => '__SLUG__']) }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                            class="far fa-times"></i></button>
                    <div data-quick-view-body></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Loader: the same two columns as the product body, so swapping the content in does not jump. --}}
    <template data-quick-view-skeleton>
        <div class="row quick_view_skeleton" role="status" aria-busy="true">
            <span class="visually-hidden">Loading product…</span>
            <div class="col-xl-6 col-12 col-sm-10 col-md-8 col-lg-6 m-auto display">
                <div class="quick_view_skeleton_img"></div>
            </div>
            <div class="col-xl-6 col-12 col-sm-12 col-md-12 col-lg-6">
                <div class="quick_view_skeleton_text">
                    <span class="quick_view_skeleton_bar is-title"></span>
                    <span class="quick_view_skeleton_bar is-badge"></span>
                    <span class="quick_view_skeleton_bar is-price"></span>
                    <span class="quick_view_skeleton_bar is-rating"></span>
                    <span class="quick_view_skeleton_bar is-line"></span>
                    <span class="quick_view_skeleton_bar is-line is-short"></span>
                    <span class="quick_view_skeleton_row">
                        <span class="quick_view_skeleton_bar is-label"></span>
                        <span class="quick_view_skeleton_dot"></span><span class="quick_view_skeleton_dot"></span><span class="quick_view_skeleton_dot"></span>
                    </span>
                    <span class="quick_view_skeleton_row">
                        <span class="quick_view_skeleton_bar is-label"></span>
                        <span class="quick_view_skeleton_chip"></span><span class="quick_view_skeleton_chip"></span><span class="quick_view_skeleton_chip"></span>
                    </span>
                    <span class="quick_view_skeleton_row">
                        <span class="quick_view_skeleton_bar is-button"></span>
                        <span class="quick_view_skeleton_bar is-button"></span>
                    </span>
                </div>
            </div>
        </div>
    </template>
</section>
