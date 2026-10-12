<?php

namespace App\Listeners;

use App\Events\VendorCreatedByAdmin;
use App\Notifications\Vendor\VendorWelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Password;
use Throwable;

class SendVendorWelcomeNotification implements ShouldQueue
{
    public function handle(VendorCreatedByAdmin $event): void
    {
        try {
            $user = $event->vendor->user;

            // New accounts get a set-password link from the web password broker; no password is ever generated for sending.
            $token = $event->userCreated ? Password::broker('users')->createToken($user) : null;

            $user->notify(new VendorWelcomeNotification($event->vendor, $token));
        } catch (Throwable $e) {
            // A mail outage must never undo the shop that was just created.
            report($e);
        }
    }
}
