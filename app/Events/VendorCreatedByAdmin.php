<?php

namespace App\Events;

use App\Models\Vendor;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorCreatedByAdmin
{
    use Dispatchable, SerializesModels;

    /**
     * @param  bool  $userCreated  true when a new account was made (it needs a set-password link)
     */
    public function __construct(public readonly Vendor $vendor, public readonly bool $userCreated) {}
}
