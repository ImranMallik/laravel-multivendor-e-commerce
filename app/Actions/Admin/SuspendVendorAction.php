<?php

namespace App\Actions\Admin;

use App\Enums\VendorStatus;
use App\Models\Vendor;

class SuspendVendorAction
{
    public function execute(Vendor $vendor): Vendor
    {
        VendorStatusTransition::assert($vendor, VendorStatus::Suspended);

        $vendor->status = VendorStatus::Suspended;
        $vendor->save();

        return $vendor;
    }
}
