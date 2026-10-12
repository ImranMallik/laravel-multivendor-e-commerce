<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation to the applicant (signup, "Become a Seller" and re-application).
 */
class VendorApplicationReceivedNotification extends Notification
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
            ->subject('We received your seller application')
            ->greeting("Hello {$notifiable->name},")
            ->line('Thanks for applying to sell on '.config('app.name')." with **{$this->vendor->shop_name}**.")
            ->line('Our team will review it and email you as soon as there is a decision. You can keep shopping with your account in the meantime.')
            ->action('View application status', route('account.seller.show'));
    }
}
