<?php

namespace App\Services;

use App\Enums\ProductApprovalStatus;
use App\Events\ProductApproved;
use App\Events\ProductRejected;
use App\Models\Admin;
use App\Models\Product;
use Closure;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * The only place that changes a product's approval status. Every change is checked against
 * ProductApprovalStatus::allowedTransitions(), runs in a transaction and writes a log row.
 */
class ProductApprovalService
{
    public function approve(Product $product, Admin $admin): Product
    {
        $this->transition($product, ProductApprovalStatus::Approved, $admin, null, null, function (Product $p) {
            $p->rejection_reason = null;
        });

        if (! $product->isInHouse()) {
            event(new ProductApproved($product));
        }

        return $product;
    }

    public function reject(Product $product, string $reason, Admin $admin): Product
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('A reason is required when rejecting a product.');
        }

        $this->transition($product, ProductApprovalStatus::Rejected, $admin, $reason, null, function (Product $p) use ($reason) {
            $p->rejection_reason = $reason;
        });

        if (! $product->isInHouse()) {
            event(new ProductRejected($product));
        }

        return $product;
    }

    /**
     * Back to Pending after a vendor edits an approved product or resubmits a rejected one.
     * Does nothing when the product is already pending.
     */
    public function markPending(Product $product, string $note): Product
    {
        if ($product->approval_status === ProductApprovalStatus::Pending) {
            return $product;
        }

        $this->transition($product, ProductApprovalStatus::Pending, null, null, $note, function (Product $p) {
            $p->rejection_reason = null;
        });

        return $product;
    }

    /**
     * First history entry, written when the product is created (inside the creating transaction).
     */
    public function logCreated(Product $product, ?Admin $admin, string $note): void
    {
        $product->approvalLogs()->create([
            'from_status' => null,
            'to_status' => $product->approval_status,
            'admin_id' => $admin?->id,
            'note' => $note,
        ]);
    }

    /**
     * @param  Closure(Product): void  $apply
     */
    private function transition(Product $product, ProductApprovalStatus $to, ?Admin $admin, ?string $reason, ?string $note, Closure $apply): void
    {
        DB::transaction(function () use ($product, $to, $admin, $reason, $note, $apply) {
            Product::whereKey($product->getKey())->lockForUpdate()->first();
            $product->refresh();

            $from = $product->approval_status;

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("A {$from->label()} product cannot be changed to {$to->label()}.");
            }

            $product->approval_status = $to;
            $apply($product);
            $product->save();

            $product->approvalLogs()->create([
                'from_status' => $from,
                'to_status' => $to,
                'reason' => $reason,
                'admin_id' => $admin?->id,
                'note' => $note,
            ]);
        });
    }
}
