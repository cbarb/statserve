<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupBoostCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Boost cancelled for {$this->group->name}")
            ->greeting("Hey {$notifiable->name},")
            ->line("Your boost for **{$this->group->name}** has been cancelled. The group will continue to have unlimited match logging until the end of the current billing period.")
            ->line('After that, the group will return to the free limit of 5 matches per week.')
            ->action('View Billing', url('/billing'))
            ->line('You can reactivate the boost any time.');
    }
}
