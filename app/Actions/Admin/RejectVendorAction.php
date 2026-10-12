<?php

namespace App\Actions\Admin;

use App\Models\Admin;
use App\Models\Vendor;
use App\Services\VendorStatusService;

class RejectVendorAction
{
    public function __construct(private readonly VendorStatusService $status) {}

    public function execute(Vendor $vendor, string $reason, ?Admin $admin = null): Vendor
    {
        return $this->status->reject($vendor, $reason, $admin);
    }
}
