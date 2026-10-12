<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To every active admin: a shop application is waiting. Mail plus a database entry for the navbar bell.
 */
class NewVendorApplication extends Notification
{
    use Queueable;

    public function __construct(private readonly Vendor $vendor) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New vendor application')
            ->line("**{$this->vendor->shop_name}** applied to become a vendor and is waiting for approval.")
            ->action('Review application', route('admin.vendors.show', $this->vendor));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'title' => 'New vendor application',
            'message' => "{$this->vendor->shop_name} is waiting for approval.",
            'url' => route('admin.vendors.show', $this->vendor),
        ];
    }
}
