<?php

namespace App\Listeners;

use App\Events\ProductRejected;
use App\Notifications\Vendor\ProductRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SendProductRejectedNotification implements ShouldQueue
{
    public function handle(ProductRejected $event): void
    {
        try {
            $event->product->vendor?->user?->notify(new ProductRejectedNotification($event->product));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
