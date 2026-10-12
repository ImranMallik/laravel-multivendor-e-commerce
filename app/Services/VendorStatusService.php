<?php

namespace App\Services;

use App\Enums\VendorSource;
use App\Enums\VendorStatus;
use App\Events\VendorApplicationSubmitted;
use App\Events\VendorApproved;
use App\Events\VendorReactivated;
use App\Events\VendorRejected;
use App\Events\VendorSuspended;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use Closure;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * The only place that sets a vendor's status. Every change is validated against
 * VendorStatus::allowedTransitions(), runs in a transaction, writes a vendor_status_logs row,
 * and fires its event after the transaction has committed.
 */
class VendorStatusService
{
    /**
     * Create the shop of a user with its first status and log entry. Runs inside the caller's
     * transaction and fires no event (the caller decides which one applies).
     *
     * @param  array{shop_name: string, shop_phone?: string|null, address: string, logo?: string|null}  $shop
     */
    public function open(User $user, array $shop, VendorSource $source, ?Admin $admin = null): Vendor
    {
        return DB::transaction(function () use ($user, $shop, $source, $admin) {
            $vendor = new Vendor([
                'shop_name' => $shop['shop_name'],
                'phone' => $shop['shop_phone'] ?? null,
                'address' => $shop['address'],
                'logo' => $shop['logo'] ?? null,
            ]);
            $vendor->user()->associate($user);
            $vendor->source = $source;

            if ($source === VendorSource::Admin) {
                $vendor->status = VendorStatus::Approved;
                $vendor->created_by = $admin?->id;
                $vendor->reviewed_by = $admin?->id;
                $vendor->reviewed_at = now();
                $vendor->approved_at = now();
            } else {
                $vendor->status = VendorStatus::Pending;
                $vendor->applied_at = now();
            }

            $vendor->save();

            $vendor->statusLogs()->create([
                'from_status' => null,
                'to_status' => $vendor->status,
                'admin_id' => $admin?->id,
                'note' => $source === VendorSource::Admin ? 'Created by admin' : 'Application submitted',
            ]);

            return $vendor;
        });
    }

    public function approve(Vendor $vendor, ?Admin $admin = null): Vendor
    {
        $this->transition($vendor, VendorStatus::Approved, $admin, null, null, function (Vendor $v) use ($admin) {
            $v->rejection_reason = null;
            $v->approved_at = now();
            $v->reviewed_by = $admin?->id;
            $v->reviewed_at = now();
        });

        event(new VendorApproved($vendor));

        return $vendor;
    }

    public function reject(Vendor $vendor, string $reason, ?Admin $admin = null): Vendor
    {
        $reason = $this->requireReason($reason, 'rejecting');

        $this->transition($vendor, VendorStatus::Rejected, $admin, $reason, null, function (Vendor $v) use ($admin, $reason) {
            $v->rejection_reason = $reason;
            $v->approved_at = null;
            $v->reviewed_by = $admin?->id;
            $v->reviewed_at = now();
        });

        event(new VendorRejected($vendor));

        return $vendor;
    }

    public function suspend(Vendor $vendor, string $reason, ?Admin $admin = null): Vendor
    {
        $reason = $this->requireReason($reason, 'suspending');

        $this->transition($vendor, VendorStatus::Suspended, $admin, $reason, null, function (Vendor $v) use ($admin) {
            $v->reviewed_by = $admin?->id;
            $v->reviewed_at = now();
        });

        event(new VendorSuspended($vendor));

        return $vendor;
    }

    public function reactivate(Vendor $vendor, ?Admin $admin = null): Vendor
    {
        $this->transition($vendor, VendorStatus::Approved, $admin, null, null, function (Vendor $v) use ($admin) {
            $v->reviewed_by = $admin?->id;
            $v->reviewed_at = now();
        });

        event(new VendorReactivated($vendor));

        return $vendor;
    }

    /**
     * Rejected -> Pending, done by the vendor. Optionally updates the shop details in the same transaction.
     *
     * @param  array<string, mixed>  $details  shop attributes to save with the re-application (shop_name, phone, address, logo)
     */
    public function reapply(Vendor $vendor, array $details = []): Vendor
    {
        $this->transition($vendor, VendorStatus::Pending, null, null, 'Re-applied', function (Vendor $v) use ($details) {
            $v->fill($details);
            $v->rejection_reason = null;
            $v->applied_at = now();
            $v->reviewed_by = null;
            $v->reviewed_at = null;
        });

        event(new VendorApplicationSubmitted($vendor));

        return $vendor;
    }

    /**
     * @param  Closure(Vendor): void  $apply  sets the columns that belong to this transition
     */
    private function transition(Vendor $vendor, VendorStatus $to, ?Admin $admin, ?string $reason, ?string $note, Closure $apply): void
    {
        DB::transaction(function () use ($vendor, $to, $admin, $reason, $note, $apply) {
            // Lock and reload so two simultaneous actions cannot both pass the check below.
            Vendor::whereKey($vendor->getKey())->lockForUpdate()->first();
            $vendor->refresh();

            $from = $vendor->status;

            if (! $from->canTransitionTo($to)) {
                throw new DomainException("A {$from->label()} vendor cannot be changed to {$to->label()}.");
            }

            $vendor->status = $to;
            $apply($vendor);
            $vendor->save();

            $vendor->statusLogs()->create([
                'from_status' => $from,
                'to_status' => $to,
                'reason' => $reason,
                'admin_id' => $admin?->id,
                'note' => $note,
            ]);
        });
    }

    private function requireReason(string $reason, string $action): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException("A reason is required when {$action} a vendor.");
        }

        return $reason;
    }
}
