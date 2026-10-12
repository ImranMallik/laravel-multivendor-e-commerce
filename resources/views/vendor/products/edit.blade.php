@extends('dashboard.layouts.app')

@section('title', 'Edit Product')
@section('page-title', 'edit product')

@section('content')
    @switch($product->approval_status)
        @case(\App\Enums\ProductApprovalStatus::Rejected)
            <div class="alert alert-danger">
                <strong>This product was not approved.</strong> Reason: {{ $product->rejection_reason }}<br>
                Fix it below; saving sends it for review again.
            </div>
            @break
        @case(\App\Enums\ProductApprovalStatus::Pending)
            <div class="alert alert-warning">This product is waiting for review. You can still change it.</div>
            @break
        @default
            @if ($reviewRequired)
                <div class="alert alert-info">
                    Changing the name, prices, descriptions, images or category sends this product back for review,
                    and it is hidden from the store until it is approved again. Stock and status changes do not.
                </div>
            @endif
    @endswitch

    @include('vendor.products.partials.form', [
        'action' => route('vendor.products.update', $product),
        'method' => 'PUT',
        'product' => $product,
    ])
@endsection
