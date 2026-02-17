<?php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        $challenges = [
            // Weekly Bonus Log challenges
            ['slug' => 'play-3-matches-week', 'name' => 'Play 3 Matches', 'description' => 'Play 3 matches this week', 'type' => 'weekly_bonus_log', 'difficulty' => 'easy', 'criteria_type' => 'matches_played_week', 'criteria_value' => 3, 'xp_reward' => 30, 'bonus_logs_reward' => 1],
            ['slug' => 'win-2-in-session', 'name' => 'Session Winner', 'description' => 'Win 2 matches in a single session', 'type' => 'weekly_bonus_log', 'difficulty' => 'easy', 'criteria_type' => 'session_wins', 'criteria_value' => 2, 'xp_reward' => 30, 'bonus_logs_reward' => 1],
            ['slug' => 'play-2-days', 'name' => 'Two-Day Player', 'description' => 'Play on 2 different days this week', 'type' => 'weekly_bonus_log', 'difficulty' => 'easy', 'criteria_type' => 'play_days_week', 'criteria_value' => 2, 'xp_reward' => 30, 'bonus_logs_reward' => 1],
            ['slug' => 'win-5-matches-week', 'name' => 'Weekly Victor', 'description' => 'Win 5 matches this week', 'type' => 'weekly_bonus_log', 'difficulty' => 'medium', 'criteria_type' => 'wins_week', 'criteria_value' => 5, 'xp_reward' => 50, 'bonus_logs_reward' => 2],
            ['slug' => 'play-singles', 'name' => 'Solo Showdown', 'description' => 'Play a 1v1 match', 'type' => 'weekly_bonus_log', 'difficulty' => 'medium', 'criteria_type' => 'singles_played', 'criteria_value' => 1, 'xp_reward' => 30, 'bonus_logs_reward' => 1],
            ['slug' => 'new-partner-win', 'name' => 'New Chemistry', 'description' => 'Win with a partner you\'ve never paired with', 'type' => 'weekly_bonus_log', 'difficulty' => 'medium', 'criteria_type' => 'new_partner_win', 'criteria_value' => 1, 'xp_reward' => 50, 'bonus_logs_reward' => 2],
            ['slug' => 'win-3-in-row', 'name' => 'Hot Streak', 'description' => 'Win 3 matches in a row', 'type' => 'weekly_bonus_log', 'difficulty' => 'hard', 'criteria_type' => 'win_streak_week', 'criteria_value' => 3, 'xp_reward' => 75, 'bonus_logs_reward' => 3],
            ['slug' => 'play-2-groups', 'name' => 'Group Traveler', 'description' => 'Play matches in 2 different groups', 'type' => 'weekly_bonus_log', 'difficulty' => 'hard', 'criteria_type' => 'groups_played_week', 'criteria_value' => 2, 'xp_reward' => 50, 'bonus_logs_reward' => 2],
            ['slug' => 'shutout-victory', 'name' => 'Perfect Game', 'description' => 'Achieve a shutout victory', 'type' => 'weekly_bonus_log', 'difficulty' => 'hard', 'criteria_type' => 'shutout_week', 'criteria_value' => 1, 'xp_reward' => 75, 'bonus_logs_reward' => 3],

            // Daily challenges (XP only)
            ['slug' => 'daily-play', 'name' => 'Daily Match', 'description' => 'Play a match today', 'type' => 'daily', 'difficulty' => 'easy', 'criteria_type' => 'matches_played_day', 'criteria_value' => 1, 'xp_reward' => 15, 'bonus_logs_reward' => 0],
            ['slug' => 'daily-win', 'name' => 'Daily Win', 'description' => 'Win a match today', 'type' => 'daily', 'difficulty' => 'easy', 'criteria_type' => 'wins_day', 'criteria_value' => 1, 'xp_reward' => 20, 'bonus_logs_reward' => 0],
            ['slug' => 'daily-points', 'name' => 'Point Scorer', 'description' => 'Score 30+ points across all matches today', 'type' => 'daily', 'difficulty' => 'medium', 'criteria_type' => 'points_scored_day', 'criteria_value' => 30, 'xp_reward' => 25, 'bonus_logs_reward' => 0],

            // Weekly XP challenges
            ['slug' => 'weekly-win-8', 'name' => 'Dominant Week', 'description' => 'Win 8 matches this week', 'type' => 'weekly_xp', 'difficulty' => 'hard', 'criteria_type' => 'wins_week', 'criteria_value' => 8, 'xp_reward' => 75, 'bonus_logs_reward' => 0],
            ['slug' => 'weekly-4-days', 'name' => 'Dedicated Player', 'description' => 'Play on 4 different days this week', 'type' => 'weekly_xp', 'difficulty' => 'hard', 'criteria_type' => 'play_days_week', 'criteria_value' => 4, 'xp_reward' => 100, 'bonus_logs_reward' => 0],
            ['slug' => 'weekly-multi-group', 'name' => 'Ambassador', 'description' => 'Win a match in 2 different groups this week', 'type' => 'weekly_xp', 'difficulty' => 'medium', 'criteria_type' => 'groups_won_week', 'criteria_value' => 2, 'xp_reward' => 60, 'bonus_logs_reward' => 0],
        ];

        foreach ($challenges as $challenge) {
            Challenge::create(array_merge(['is_active' => true], $challenge));
        }
    }
}
