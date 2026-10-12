<?php

namespace App\Listeners;

use App\Events\VendorApplicationSubmitted;
use App\Models\Admin;
use App\Notifications\Vendor\NewVendorApplication;
use App\Notifications\Vendor\VendorApplicationReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyAboutVendorApplication implements ShouldQueue
{
    public function handle(VendorApplicationSubmitted $event): void
    {
        // Each part is guarded on its own: a mail outage must never block the application itself.
        try {
            Notification::send(Admin::where('status', true)->get(), new NewVendorApplication($event->vendor));
        } catch (Throwable $e) {
            report($e);
        }

        try {
            $event->vendor->user->notify(new VendorApplicationReceivedNotification($event->vendor));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
