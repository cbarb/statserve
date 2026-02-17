<?php

use App\Models\GameMatch;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dev:max-matches {group_slug}', function (string $group_slug) {
    $group = Group::where('slug', $group_slug)->firstOrFail();
    $tz = $group->timezone ?? 'UTC';
    $weekStart = Carbon::now($tz)->startOfWeek(Carbon::MONDAY)->utc();

    $currentCount = GameMatch::where('group_id', $group->id)
        ->where('played_at', '>=', $weekStart)
        ->count();

    $needed = 5 - $currentCount;

    if ($needed <= 0) {
        $this->info("'{$group->name}' already at {$currentCount}/5 matches this week.");
        return;
    }

    $existing = GameMatch::where('group_id', $group->id)->latest()->first();
    if (!$existing) {
        $this->error("No matches found for '{$group->name}'. Play at least one game first.");
        return;
    }

    for ($i = 0; $i < $needed; $i++) {
        GameMatch::create([
            'group_id' => $group->id,
            'session_id' => $existing->session_id,
            'format' => $existing->format,
            'status' => 'completed',
            'team_1_score' => 11,
            'team_2_score' => 0,
            'winning_team' => 1,
            'logged_by' => $existing->logged_by,
            'played_at' => now(),
        ]);
    }

    $this->info("Added {$needed} dummy matches for '{$group->name}'. Weekly count is now 5/5.");
})->purpose('Fill a group\'s weekly match count to the max (5) for dev testing');

Artisan::command('dev:reset-matches {group_slug}', function (string $group_slug) {
    $group = Group::where('slug', $group_slug)->firstOrFail();
    $tz = $group->timezone ?? 'UTC';
    $weekStart = Carbon::now($tz)->startOfWeek(Carbon::MONDAY)->utc();

    $count = GameMatch::where('group_id', $group->id)
        ->where('played_at', '>=', $weekStart)
        ->update(['played_at' => $weekStart->copy()->subWeek()]);

    $this->info("Backdated {$count} matches for '{$group->name}'. Weekly count is now 0/5.");
})->purpose('Reset a group\'s weekly match count to 0 by backdating matches');
