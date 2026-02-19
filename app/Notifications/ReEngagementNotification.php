<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReEngagementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We miss you on the court!')
            ->greeting("Hey {$notifiable->name}!")
            ->line("It's been a while since your last match on StatServe. Your groups are waiting for you!")
            ->line('Jump back in and log a match — it only takes a few seconds.')
            ->action('Play Now', url('/dashboard'))
            ->line('See you on the court!');
    }
}
