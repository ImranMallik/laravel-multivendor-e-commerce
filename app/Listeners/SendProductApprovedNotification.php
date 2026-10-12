<?php

namespace App\Listeners;

use App\Events\ProductApproved;
use App\Notifications\Vendor\ProductApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendProductApprovedNotification implements ShouldQueue
{
    public function handle(ProductApproved $event): void
    {
        try {
            $event->product->vendor?->user?->notify(new ProductApprovedNotification($event->product));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
