<?php

namespace App\Notifications\Vendor;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductRejectedNotification extends Notification
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
            ->subject('Your product was not approved')
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->product->name}** (SKU {$this->product->sku}) was not approved.")
            ->line("Reason: {$this->product->rejection_reason}")
            ->line('Edit the product to fix it; saving sends it for review again.')
            ->action('Edit product', route('vendor.products.edit', $this->product));
    }
}
