<?php

namespace App\Console\Commands;

use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\TournamentStartingNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendTournamentReminders extends Command
{
    protected $signature = 'tournaments:send-reminders';

    protected $description = 'Send 24-hour reminder emails to tournament participants';

    public function handle(): int
    {
        $tournaments = Tournament::query()
            ->whereIn('status', [TournamentStatus::Registration, TournamentStatus::InProgress])
            ->where('starts_at', '>=', now()->addHours(23))
            ->where('starts_at', '<=', now()->addHours(25))
            ->with('entries')
            ->get();

        $count = 0;

        foreach ($tournaments as $tournament) {
            $userIds = $tournament->entries->pluck('user_id');
            $partnerIds = $tournament->entries->pluck('partner_id')->filter();
            $allUserIds = $userIds->merge($partnerIds)->unique();

            $users = User::whereIn('id', $allUserIds)->get();

            Notification::send($users, new TournamentStartingNotification($tournament));

            $count += $users->count();
        }

        $this->info("Sent {$count} reminder(s) for {$tournaments->count()} tournament(s).");

        return self::SUCCESS;
    }
}
