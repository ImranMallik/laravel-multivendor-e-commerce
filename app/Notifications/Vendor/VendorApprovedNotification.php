<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorApprovedNotification extends Notification
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
            ->subject('Your shop has been approved')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->vendor->shop_name} has been approved. You can now use your vendor panel.")
            ->action('Open vendor panel', route('vendor.dashboard'));
    }
}
