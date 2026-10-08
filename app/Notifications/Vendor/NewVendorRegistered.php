<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewVendorRegistered extends Notification
{
    use Queueable;

    public function __construct(private readonly Vendor $vendor) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New vendor awaiting approval')
            ->line("{$this->vendor->shop_name} has registered as a vendor and is waiting for approval.")
            ->action('Review vendor', route('admin.vendors.show', $this->vendor));
    }
}
