<?php

namespace Database\Seeders;

use App\Enums\ChallengeStatus;
use App\Enums\XpSourceType;
use App\Models\Badge;
use App\Models\Challenge;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserChallenge;
use App\Models\UserXpEvent;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $badges = Badge::where('is_secret', false)->get();
        $dailyChallenges = Challenge::where('type', 'daily')->get();
        $weeklyChallenges = Challenge::where('type', '!=', 'daily')->get();

        foreach ($users as $user) {
            // Add some XP events
            $eventCount = rand(5, 15);
            for ($i = 0; $i < $eventCount; $i++) {
                UserXpEvent::create([
                    'user_id' => $user->id,
                    'source_type' => fake()->randomElement(XpSourceType::cases()),
                    'xp_amount' => fake()->randomElement([10, 15, 20, 25, 30, 50]),
                    'description' => fake()->randomElement([
                        'Played a match',
                        'Won a match',
                        'Completed daily challenge',
                        'First match of the day',
                        'Earned a badge',
                    ]),
                ]);
            }

            // Award 1-3 random badges per user
            $userBadges = $badges->random(min(rand(1, 3), $badges->count()));
            foreach ($userBadges as $badge) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => fake()->dateTimeBetween('-30 days', 'now'),
                    'is_pinned' => fake()->boolean(30),
                ]);
            }

            // Assign active daily challenge
            if ($dailyChallenges->isNotEmpty()) {
                $daily = $dailyChallenges->random();
                UserChallenge::create([
                    'user_id' => $user->id,
                    'challenge_id' => $daily->id,
                    'assigned_at' => now()->startOfDay(),
                    'expires_at' => now()->endOfDay(),
                    'progress' => rand(0, $daily->criteria_value),
                    'target' => $daily->criteria_value,
                    'status' => ChallengeStatus::Active,
                ]);
            }

            // Assign 3 weekly challenges (1 easy, 1 medium, 1 hard)
            if ($weeklyChallenges->count() >= 3) {
                $easy = $weeklyChallenges->where('difficulty', 'easy')->random();
                $medium = $weeklyChallenges->where('difficulty', 'medium')->random();
                $hard = $weeklyChallenges->where('difficulty', 'hard')->random();

                foreach ([$easy, $medium, $hard] as $ch) {
                    UserChallenge::create([
                        'user_id' => $user->id,
                        'challenge_id' => $ch->id,
                        'assigned_at' => now()->startOfWeek(),
                        'expires_at' => now()->endOfWeek(),
                        'progress' => rand(0, $ch->criteria_value),
                        'target' => $ch->criteria_value,
                        'status' => ChallengeStatus::Active,
                    ]);
                }
            }
        }
    }
}
