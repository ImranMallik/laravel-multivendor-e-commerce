<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an admin creates a shop for someone.
 * With a token the account is new and the mail carries a "set your password" link (the web password
 * broker's reset link). Without one the person already has an account and just logs in.
 */
class VendorWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Vendor $vendor, private readonly ?string $token = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your seller account on '.config('app.name'))
            ->greeting("Welcome, {$notifiable->name}!")
            ->line("An administrator created the shop **{$this->vendor->shop_name}** for you on ".config('app.name').'. It is already approved.');

        if ($this->token) {
            $minutes = config('auth.passwords.users.expire');

            return $mail
                ->line('Choose a password to sign in and open your seller dashboard.')
                ->action('Set your password', url(route('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false)))
                ->line("This link works for {$minutes} minutes. If it has expired, use \"Forgot your password?\" on the sign-in page.");
        }

        return $mail
            ->line('Sign in with your existing account to open your seller dashboard.')
            ->action('Open seller dashboard', route('vendor.dashboard'));
    }
}
