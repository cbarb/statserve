<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // Win Milestones
            ['slug' => 'victor-bronze', 'name' => 'Victor', 'description' => 'Win 10 matches', 'category' => 'wins', 'tier' => 'bronze', 'criteria_type' => 'wins_total', 'criteria_value' => 10, 'xp_reward' => 50],
            ['slug' => 'victor-silver', 'name' => 'Victor', 'description' => 'Win 50 matches', 'category' => 'wins', 'tier' => 'silver', 'criteria_type' => 'wins_total', 'criteria_value' => 50, 'xp_reward' => 50],
            ['slug' => 'victor-gold', 'name' => 'Victor', 'description' => 'Win 150 matches', 'category' => 'wins', 'tier' => 'gold', 'criteria_type' => 'wins_total', 'criteria_value' => 150, 'xp_reward' => 50],
            ['slug' => 'victor-platinum', 'name' => 'Victor', 'description' => 'Win 500 matches', 'category' => 'wins', 'tier' => 'platinum', 'criteria_type' => 'wins_total', 'criteria_value' => 500, 'xp_reward' => 50],

            ['slug' => 'streak-master-bronze', 'name' => 'Streak Master', 'description' => 'Win 3 in a row', 'category' => 'wins', 'tier' => 'bronze', 'criteria_type' => 'win_streak', 'criteria_value' => 3, 'xp_reward' => 50],
            ['slug' => 'streak-master-silver', 'name' => 'Streak Master', 'description' => 'Win 5 in a row', 'category' => 'wins', 'tier' => 'silver', 'criteria_type' => 'win_streak', 'criteria_value' => 5, 'xp_reward' => 50],
            ['slug' => 'streak-master-gold', 'name' => 'Streak Master', 'description' => 'Win 8 in a row', 'category' => 'wins', 'tier' => 'gold', 'criteria_type' => 'win_streak', 'criteria_value' => 8, 'xp_reward' => 50],
            ['slug' => 'streak-master-platinum', 'name' => 'Streak Master', 'description' => 'Win 12 in a row', 'category' => 'wins', 'tier' => 'platinum', 'criteria_type' => 'win_streak', 'criteria_value' => 12, 'xp_reward' => 50],

            ['slug' => 'shutout-king-bronze', 'name' => 'Shutout King', 'description' => 'Win 1 shutout', 'category' => 'wins', 'tier' => 'bronze', 'criteria_type' => 'shutouts', 'criteria_value' => 1, 'xp_reward' => 50],
            ['slug' => 'shutout-king-silver', 'name' => 'Shutout King', 'description' => 'Win 5 shutouts', 'category' => 'wins', 'tier' => 'silver', 'criteria_type' => 'shutouts', 'criteria_value' => 5, 'xp_reward' => 50],
            ['slug' => 'shutout-king-gold', 'name' => 'Shutout King', 'description' => 'Win 15 shutouts', 'category' => 'wins', 'tier' => 'gold', 'criteria_type' => 'shutouts', 'criteria_value' => 15, 'xp_reward' => 50],
            ['slug' => 'shutout-king-platinum', 'name' => 'Shutout King', 'description' => 'Win 50 shutouts', 'category' => 'wins', 'tier' => 'platinum', 'criteria_type' => 'shutouts', 'criteria_value' => 50, 'xp_reward' => 50],

            ['slug' => 'comeback-kid-bronze', 'name' => 'Comeback Kid', 'description' => 'Win 1 comeback from 5+ down', 'category' => 'wins', 'tier' => 'bronze', 'criteria_type' => 'comebacks', 'criteria_value' => 1, 'xp_reward' => 50],
            ['slug' => 'comeback-kid-silver', 'name' => 'Comeback Kid', 'description' => 'Win 5 comebacks from 5+ down', 'category' => 'wins', 'tier' => 'silver', 'criteria_type' => 'comebacks', 'criteria_value' => 5, 'xp_reward' => 50],
            ['slug' => 'comeback-kid-gold', 'name' => 'Comeback Kid', 'description' => 'Win 15 comebacks from 5+ down', 'category' => 'wins', 'tier' => 'gold', 'criteria_type' => 'comebacks', 'criteria_value' => 15, 'xp_reward' => 50],
            ['slug' => 'comeback-kid-platinum', 'name' => 'Comeback Kid', 'description' => 'Win 50 comebacks from 5+ down', 'category' => 'wins', 'tier' => 'platinum', 'criteria_type' => 'comebacks', 'criteria_value' => 50, 'xp_reward' => 50],

            // Play Volume
            ['slug' => 'court-warrior-bronze', 'name' => 'Court Warrior', 'description' => 'Play 25 matches', 'category' => 'volume', 'tier' => 'bronze', 'criteria_type' => 'matches_played', 'criteria_value' => 25, 'xp_reward' => 50],
            ['slug' => 'court-warrior-silver', 'name' => 'Court Warrior', 'description' => 'Play 100 matches', 'category' => 'volume', 'tier' => 'silver', 'criteria_type' => 'matches_played', 'criteria_value' => 100, 'xp_reward' => 50],
            ['slug' => 'court-warrior-gold', 'name' => 'Court Warrior', 'description' => 'Play 300 matches', 'category' => 'volume', 'tier' => 'gold', 'criteria_type' => 'matches_played', 'criteria_value' => 300, 'xp_reward' => 50],
            ['slug' => 'court-warrior-platinum', 'name' => 'Court Warrior', 'description' => 'Play 1000 matches', 'category' => 'volume', 'tier' => 'platinum', 'criteria_type' => 'matches_played', 'criteria_value' => 1000, 'xp_reward' => 50],

            ['slug' => 'session-beast-bronze', 'name' => 'Session Beast', 'description' => 'Play 5 matches in one session', 'category' => 'volume', 'tier' => 'bronze', 'criteria_type' => 'session_matches', 'criteria_value' => 5, 'xp_reward' => 50],
            ['slug' => 'session-beast-silver', 'name' => 'Session Beast', 'description' => 'Play 8 matches in one session', 'category' => 'volume', 'tier' => 'silver', 'criteria_type' => 'session_matches', 'criteria_value' => 8, 'xp_reward' => 50],
            ['slug' => 'session-beast-gold', 'name' => 'Session Beast', 'description' => 'Play 12 matches in one session', 'category' => 'volume', 'tier' => 'gold', 'criteria_type' => 'session_matches', 'criteria_value' => 12, 'xp_reward' => 50],
            ['slug' => 'session-beast-platinum', 'name' => 'Session Beast', 'description' => 'Play 15+ matches in one session', 'category' => 'volume', 'tier' => 'platinum', 'criteria_type' => 'session_matches', 'criteria_value' => 15, 'xp_reward' => 50],

            // Social / Group
            ['slug' => 'team-player', 'name' => 'Team Player', 'description' => 'Play with every member of a group as a partner', 'category' => 'social', 'tier' => 'gold', 'criteria_type' => 'all_partners_in_group', 'criteria_value' => 1, 'xp_reward' => 50],
            ['slug' => 'rival', 'name' => 'Rival', 'description' => 'Play 20+ matches against the same person', 'category' => 'social', 'tier' => 'gold', 'criteria_type' => 'h2h_matches', 'criteria_value' => 20, 'xp_reward' => 50],
            ['slug' => 'group-hopper-bronze', 'name' => 'Group Hopper', 'description' => 'Be a member of 3 groups', 'category' => 'social', 'tier' => 'bronze', 'criteria_type' => 'groups_joined', 'criteria_value' => 3, 'xp_reward' => 50],
            ['slug' => 'group-hopper-silver', 'name' => 'Group Hopper', 'description' => 'Be a member of 5 groups', 'category' => 'social', 'tier' => 'silver', 'criteria_type' => 'groups_joined', 'criteria_value' => 5, 'xp_reward' => 50],

            // Partnership (Doubles)
            ['slug' => 'dynamic-duo-bronze', 'name' => 'Dynamic Duo', 'description' => 'Win 10 matches with the same partner', 'category' => 'partnership', 'tier' => 'bronze', 'criteria_type' => 'partner_wins', 'criteria_value' => 10, 'xp_reward' => 50],
            ['slug' => 'dynamic-duo-silver', 'name' => 'Dynamic Duo', 'description' => 'Win 25 matches with the same partner', 'category' => 'partnership', 'tier' => 'silver', 'criteria_type' => 'partner_wins', 'criteria_value' => 25, 'xp_reward' => 50],
            ['slug' => 'dynamic-duo-gold', 'name' => 'Dynamic Duo', 'description' => 'Win 50 matches with the same partner', 'category' => 'partnership', 'tier' => 'gold', 'criteria_type' => 'partner_wins', 'criteria_value' => 50, 'xp_reward' => 50],
            ['slug' => 'dynamic-duo-platinum', 'name' => 'Dynamic Duo', 'description' => 'Win 100 matches with the same partner', 'category' => 'partnership', 'tier' => 'platinum', 'criteria_type' => 'partner_wins', 'criteria_value' => 100, 'xp_reward' => 50],

            ['slug' => 'versatile-bronze', 'name' => 'Versatile', 'description' => 'Win with 5 different partners', 'category' => 'partnership', 'tier' => 'bronze', 'criteria_type' => 'unique_partners_won', 'criteria_value' => 5, 'xp_reward' => 50],
            ['slug' => 'versatile-silver', 'name' => 'Versatile', 'description' => 'Win with 10 different partners', 'category' => 'partnership', 'tier' => 'silver', 'criteria_type' => 'unique_partners_won', 'criteria_value' => 10, 'xp_reward' => 50],

            // Secret Badges
            ['slug' => 'pickle-rick', 'name' => 'Pickle Rick', 'description' => 'Win 3 in a row after losing 3 in a row', 'category' => 'secret', 'tier' => 'gold', 'criteria_type' => 'pickle_rick', 'criteria_value' => 1, 'xp_reward' => 50, 'is_secret' => true],
            ['slug' => 'the-sandwich', 'name' => 'The Sandwich', 'description' => 'Win, lose, win, lose, win in consecutive games', 'category' => 'secret', 'tier' => 'gold', 'criteria_type' => 'sandwich_pattern', 'criteria_value' => 1, 'xp_reward' => 50, 'is_secret' => true],
            ['slug' => 'zero-to-hero', 'name' => 'Zero to Hero', 'description' => 'Go from last place to first in group leaderboard', 'category' => 'secret', 'tier' => 'platinum', 'criteria_type' => 'last_to_first', 'criteria_value' => 1, 'xp_reward' => 50, 'is_secret' => true],
            ['slug' => 'marathon', 'name' => 'Marathon', 'description' => 'Play 10+ matches in a single session', 'category' => 'secret', 'tier' => 'gold', 'criteria_type' => 'session_matches', 'criteria_value' => 10, 'xp_reward' => 50, 'is_secret' => true],
        ];

        foreach ($badges as $badge) {
            Badge::create(array_merge([
                'icon' => null,
                'is_secret' => false,
            ], $badge));
        }
    }
}
