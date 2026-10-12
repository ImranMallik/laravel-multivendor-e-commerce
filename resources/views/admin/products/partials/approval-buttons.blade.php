{{--
    Approve / Reject buttons, used in the table and on the product page (public/admin-assets/js/product-actions.js).
    Expects: $product, optional $size.
--}}
@php($size ??= null)

@if ($product->approval_status->canTransitionTo(\App\Enums\ProductApprovalStatus::Approved))
    <x-admin.button variant="success" icon="fas fa-check" :size="$size" tooltip="Approve"
                    data-product-approval="approve"
                    data-url="{{ route('admin.products.approve', $product) }}"
                    data-title="{{ $product->name }}" />
@endif
@if ($product->approval_status->canTransitionTo(\App\Enums\ProductApprovalStatus::Rejected))
    <x-admin.button variant="warning" icon="fas fa-times" :size="$size" tooltip="Reject"
                    data-product-approval="reject"
                    data-url="{{ route('admin.products.reject', $product) }}"
                    data-title="{{ $product->name }}" />
@endif
