{{-- Approval badge; a rejected product shows its reason on hover and in a dialog on click. --}}
@if ($product->approval_status === \App\Enums\ProductApprovalStatus::Rejected)
    <button type="button" class="badge bg-danger border-0" title="{{ $product->rejection_reason }}"
            data-rejection-reason="{{ $product->rejection_reason }}" data-title="{{ $product->name }}">
        Rejected <i class="far fa-info-circle"></i>
    </button>
@else
    <span class="badge bg-{{ $product->approval_status->color() }}">{{ $product->approval_status->label() }}</span>
@endif
