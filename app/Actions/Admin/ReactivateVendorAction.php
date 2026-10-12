<?php

namespace App\Actions\Admin;

use App\Models\Admin;
use App\Models\Vendor;
use App\Services\VendorStatusService;

class ReactivateVendorAction
{
    public function __construct(private readonly VendorStatusService $status) {}

    public function execute(Vendor $vendor, ?Admin $admin = null): Vendor
    {
        return $this->status->reactivate($vendor, $admin);
    }
}
