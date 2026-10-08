<?php

namespace App\Actions\Admin;

use App\Enums\VendorStatus;
use App\Models\Vendor;

class ReactivateVendorAction
{
    public function execute(Vendor $vendor): Vendor
    {
        VendorStatusTransition::assert($vendor, VendorStatus::Approved);

        if ($vendor->status !== VendorStatus::Suspended) {
            throw new \DomainException('Only suspended vendors can be reactivated.');
        }

        $vendor->status = VendorStatus::Approved;
        $vendor->save();

        return $vendor;
    }
}
