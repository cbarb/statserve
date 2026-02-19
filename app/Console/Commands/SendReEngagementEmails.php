<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReEngagementNotification;
use Illuminate\Console\Command;

class SendReEngagementEmails extends Command
{
    protected $signature = 'email:re-engagement
                            {--days=14 : Number of days of inactivity}
                            {--dry-run : Preview recipients without sending}';

    protected $description = 'Send re-engagement emails to users who haven\'t played recently';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $cutoff = now()->subDays($days);

        $users = User::whereHas('matchPlayers', function ($query) {
            $query->whereHas('match');
        })
            ->whereDoesntHave('matchPlayers', function ($query) use ($cutoff) {
                $query->whereHas('match', function ($q) use ($cutoff) {
                    $q->where('played_at', '>=', $cutoff);
                });
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('No inactive users found.');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} inactive user(s) (no matches in the last {$days} days):");

        foreach ($users as $user) {
            $this->line("  - {$user->name} ({$user->email})");

            if (! $dryRun) {
                $user->notify(new ReEngagementNotification);
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — no emails were sent.');
        } else {
            $this->info("Sent {$users->count()} re-engagement email(s).");
        }

        return self::SUCCESS;
    }
}
