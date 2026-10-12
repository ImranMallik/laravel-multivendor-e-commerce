<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorSuspendedNotification extends Notification
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
            ->subject('Your shop has been suspended')
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->vendor->shop_name}** is suspended, so the seller dashboard is unavailable for now.")
            ->line('Reason: '.($this->vendor->suspensionReason() ?? 'not specified'))
            ->line('You can still shop with your account. Contact support if you think this is a mistake.')
            ->action('View status', route('account.seller.show'));
    }
}
