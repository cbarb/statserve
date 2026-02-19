<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupBoostConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
        public User $purchaser,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isPurchaser = $notifiable->id === $this->purchaser->id;

        $message = (new MailMessage)
            ->subject("Group Boost activated for {$this->group->name}!");

        if ($isPurchaser) {
            $message->greeting("Hey {$notifiable->name}!")
                ->line("Your boost for **{$this->group->name}** is now active! Everyone in the group now has unlimited match logging.");
        } else {
            $message->greeting("Hey {$notifiable->name}!")
                ->line("**{$this->purchaser->name}** just boosted **{$this->group->name}**! Everyone in the group now has unlimited match logging.");
        }

        return $message
            ->action('Go to Group', url("/groups/{$this->group->slug}"))
            ->line('Time to play!');
    }
}
