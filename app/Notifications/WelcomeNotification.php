<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to StatServe!')
            ->greeting("Hey {$notifiable->name}!")
            ->line('Welcome to StatServe — the easiest way to track your pickleball stats with friends.')
            ->line('Get started by creating or joining a group, then log your first match.')
            ->action('Create a Group', url('/groups/create'))
            ->line('See you on the court!');
    }
}
