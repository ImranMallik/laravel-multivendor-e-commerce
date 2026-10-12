<?php

namespace App\Listeners;

use App\Events\VendorSuspended;
use App\Notifications\Vendor\VendorSuspendedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendVendorSuspendedNotification implements ShouldQueue
{
    public function handle(VendorSuspended $event): void
    {
        try {
            $event->vendor->user->notify(new VendorSuspendedNotification($event->vendor));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
