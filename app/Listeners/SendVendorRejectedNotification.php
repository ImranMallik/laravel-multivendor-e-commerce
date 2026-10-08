<?php

namespace App\Listeners;

use App\Events\VendorRejected;
use App\Notifications\Vendor\VendorRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendVendorRejectedNotification implements ShouldQueue
{
    public function handle(VendorRejected $event): void
    {
        try {
            $event->vendor->user->notify(new VendorRejectedNotification($event->vendor));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
