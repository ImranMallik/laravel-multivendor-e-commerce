<?php

namespace App\Listeners;

use App\Events\VendorRegistered;
use App\Models\Admin;
use App\Notifications\Vendor\NewVendorRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyAdminsOfVendorRegistration implements ShouldQueue
{
    public function handle(VendorRegistered $event): void
    {
        try {
            Notification::send(Admin::where('status', true)->get(), new NewVendorRegistered($event->vendor));
        } catch (Throwable $e) {
            // A mail outage must never undo or block the registration itself.
            report($e);
        }
    }
}
