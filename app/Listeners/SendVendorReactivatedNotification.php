<?php

namespace App\Listeners;

use App\Events\VendorReactivated;
use App\Notifications\Vendor\VendorReactivatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendVendorReactivatedNotification implements ShouldQueue
{
    public function handle(VendorReactivated $event): void
    {
        try {
            $event->vendor->user->notify(new VendorReactivatedNotification($event->vendor));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
