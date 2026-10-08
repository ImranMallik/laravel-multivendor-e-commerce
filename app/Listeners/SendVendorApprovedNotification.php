<?php

namespace App\Listeners;

use App\Events\VendorApproved;
use App\Notifications\Vendor\VendorApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendVendorApprovedNotification implements ShouldQueue
{
    public function handle(VendorApproved $event): void
    {
        try {
            $event->vendor->user->notify(new VendorApprovedNotification($event->vendor));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
