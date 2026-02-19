<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProSubscriptionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Pro subscription has been cancelled')
            ->greeting("Hey {$notifiable->name},")
            ->line('Your StatServe Pro subscription has been cancelled. You\'ll continue to have access to Pro features until the end of your current billing period.')
            ->line('After that, your account will revert to the free plan with a weekly limit of 5 matches per group.')
            ->action('Resubscribe', url('/billing'))
            ->line('We hope to see you back soon!');
    }
}
