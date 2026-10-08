<?php

namespace App\Actions\Admin;

use App\Enums\VendorStatus;
use App\Events\VendorRejected;
use App\Models\Vendor;

class RejectVendorAction
{
    public function execute(Vendor $vendor, string $reason): Vendor
    {
        VendorStatusTransition::assert($vendor, VendorStatus::Rejected);

        $vendor->status = VendorStatus::Rejected;
        $vendor->rejection_reason = $reason;
        $vendor->approved_at = null;
        $vendor->save();

        event(new VendorRejected($vendor));

        return $vendor;
    }
}
