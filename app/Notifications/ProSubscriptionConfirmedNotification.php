<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProSubscriptionConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're now a StatServe Pro!")
            ->greeting("Hey {$notifiable->name}!")
            ->line("You're officially a StatServe Pro member. Here's what you've unlocked:")
            ->line('- **Unlimited match logging** across all your groups')
            ->line('- **Advanced stats & analytics**')
            ->line('- **Priority support**')
            ->action('View Your Stats', url('/dashboard'))
            ->line('Thanks for supporting StatServe!');
    }
}
