<?php

namespace App\Events;

use App\Models\Vendor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Vendor $vendor) {}
}
