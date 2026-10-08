<?php

namespace App\Actions\Admin;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use DomainException;

/**
 * Guards the vendor status workflow:
 * Pending -> Approved|Rejected, Rejected -> Approved, Approved -> Rejected|Suspended, Suspended -> Approved.
 */
class VendorStatusTransition
{
    public static function assert(Vendor $vendor, VendorStatus $to): void
    {
        $allowed = match ($to) {
            VendorStatus::Approved => [VendorStatus::Pending, VendorStatus::Rejected, VendorStatus::Suspended],
            VendorStatus::Rejected => [VendorStatus::Pending, VendorStatus::Approved],
            VendorStatus::Suspended => [VendorStatus::Approved],
            VendorStatus::Pending => [],
        };

        if (! in_array($vendor->status, $allowed, true)) {
            throw new DomainException("A {$vendor->status->label()} vendor cannot be changed to {$to->label()}.");
        }
    }
}
