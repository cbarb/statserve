<?php

namespace App\Notifications;

use App\Models\Tournament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TournamentJoinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tournament $tournament,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->tournament;
        $location = collect([$t->city, $t->state])->filter()->implode(', ');

        return (new MailMessage)
            ->subject("You're registered for {$t->name}!")
            ->greeting("Hey {$notifiable->name}!")
            ->line("You've been registered for **{$t->name}**.")
            ->line("- **Format:** {$t->format->label()}")
            ->line("- **Starts:** {$t->starts_at->format('M j, Y g:i A')}")
            ->line($location ? "- **Location:** {$location}" : '')
            ->action('View Tournament', url("/tournaments/{$t->id}"))
            ->line('Good luck out there!');
    }
}
