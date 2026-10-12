<?php

namespace App\Notifications\Vendor;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Product $product) {}

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
            ->subject('Your product was approved')
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->product->name}** (SKU {$this->product->sku}) has been approved.")
            ->line('It is visible in the store while its status is Active.')
            ->action('View your products', route('vendor.products.index'));
    }
}
