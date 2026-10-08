<?php

namespace App\Actions\Admin;

use App\Enums\VendorStatus;
use App\Events\VendorApproved;
use App\Models\Vendor;

class ApproveVendorAction
{
    public function execute(Vendor $vendor): Vendor
    {
        VendorStatusTransition::assert($vendor, VendorStatus::Approved);

        $vendor->status = VendorStatus::Approved;
        $vendor->rejection_reason = null;
        $vendor->approved_at = now();
        $vendor->save();

        event(new VendorApproved($vendor));

        return $vendor;
    }
}
