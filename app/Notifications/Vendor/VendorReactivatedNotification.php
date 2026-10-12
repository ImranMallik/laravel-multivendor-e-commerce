<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorReactivatedNotification extends Notification
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
            ->subject('Your shop is active again')
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->vendor->shop_name}** has been reactivated. Your seller dashboard is available again.")
            ->action('Open seller dashboard', route('vendor.dashboard'));
    }
}
