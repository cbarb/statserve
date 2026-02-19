# StatServe — Complete Project Documentation

Comprehensive reference for the entire StatServe codebase. Use this to trace any feature, understand data flows, and debug issues.

---

## Table of Contents

1. [Tech Stack & Setup](#1-tech-stack--setup)
2. [Database Schema](#2-database-schema)
3. [Models & Relationships](#3-models--relationships)
4. [Enums](#4-enums)
5. [Services (Business Logic)](#5-services-business-logic)
6. [Controllers & Routes](#6-controllers--routes)
7. [Policies & Authorization](#7-policies--authorization)
8. [Form Requests (Validation)](#8-form-requests-validation)
9. [Middleware](#9-middleware)
10. [Frontend — Layouts & Components](#10-frontend--layouts--components)
11. [Frontend — Pages](#11-frontend--pages)
12. [Authentication Flow](#12-authentication-flow)
13. [Subscription & Billing System](#13-subscription--billing-system)
14. [Match & Session System](#14-match--session-system)
15. [Gamification System (XP, Badges)](#15-gamification-system-xp-badges)
16. [Stats System](#16-stats-system)
17. [Group Management](#17-group-management)
18. [Key Data Flows](#18-key-data-flows)
19. [Dev Commands & Testing](#19-dev-commands--testing)
20. [Session Changes Log](#20-session-changes-log)
21. [Debugging Guide](#21-debugging-guide)

---

## 1. Tech Stack & Setup

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.2+) |
| Frontend | Vue 3 + Inertia.js |
| Styling | Tailwind CSS + @tailwindcss/forms |
| Database | MySQL (via Docker/Sail) |
| Auth | Laravel Breeze (session-based) |
| Billing | Laravel Cashier (Stripe) |
| API Tokens | Laravel Sanctum |
| Build | Vite + laravel-vite-plugin |
| Routes in JS | Ziggy (tightenco/ziggy) |
| Font | Figtree (400, 500, 600) |
| Docker | Laravel Sail |

### Key Config Files

| File | Purpose |
|------|---------|
| `vite.config.js` | Vite build config with Laravel + Vue plugins |
| `tailwind.config.js` | Tailwind theme (Figtree font, forms plugin) |
| `jsconfig.json` | `@/*` → `resources/js/*` alias |
| `config/services.php` | Stripe price env vars |
| `bootstrap/app.php` | Middleware stack, route files |

### Startup

```bash
npm run start              # Sail + Vite dev server
# Or separately:
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev
```

### Database

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed
```

---

## 2. Database Schema

### Core Tables

**users** — `0001_01_01_000000` + `2026_02_16_000001` + `2026_02_16_193141`
- `id`, `name`, `email`, `password`, `email_verified_at`
- `avatar_url` (nullable), `timezone` (default: America/New_York)
- Cashier columns: `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`
- `remember_token`, `created_at`, `updated_at`

**groups** — `2026_02_16_000002`
- `id`, `name`, `slug` (unique), `created_by` (FK→users)
- `invite_code` (unique, 8-char), `timezone` (nullable)
- `created_at`, `updated_at`

**group_user** (pivot) — `2026_02_16_000003`
- `user_id`, `group_id`, `role` (owner/admin/member), `joined_at`

### Match Tables

**game_sessions** — `2026_02_16_000005`
- `id`, `group_id`, `started_by` (FK→users)
- `format` (singles/doubles), `team_mode` (random/handpick)
- `status` (active/completed), `player_ids` (JSON array)
- `started_at`, `finished_at`

**matches** — `2026_02_16_000006` + `2026_02_16_000023`
- `id`, `group_id`, `session_id` (nullable), `tournament_round_id` (nullable)
- `format`, `status` (in_progress/completed/cancelled)
- `team_1_score`, `team_2_score` (unsigned tinyint 0-99)
- `winning_team` (1 or 2, nullable), `logged_by` (FK→users)
- `played_at`

**match_players** — `2026_02_16_000007`
- `match_id`, `user_id`, `team` (1 or 2), `position` (left/right/solo)
- No timestamps

### Gamification Tables

**user_xp_events** — `2026_02_16_000008`
- `user_id`, `source_type` (match/challenge/badge/bonus), `source_id`
- `xp_amount`, `description`, `created_at` (immutable, no updated_at)

**user_levels** — `2026_02_16_000009`
- `user_id` (unique), `current_level` (default 1), `total_xp` (default 0)

**badges** — `2026_02_16_000010`
- `slug` (unique), `name`, `description`
- `category` (wins/volume/social/partnership/secret)
- `tier` (bronze/silver/gold/platinum)
- `criteria_type`, `criteria_value`, `xp_reward`
- `icon`, `is_secret`

**user_badges** — `2026_02_16_000011`
- `user_id`, `badge_id` (unique pair), `earned_at`, `is_pinned`

**challenges** — `2026_02_16_000012`
- `slug`, `name`, `description`, `type`, `difficulty`
- `criteria_type`, `criteria_value`, `xp_reward`, `bonus_logs_reward`
- `is_active`

**user_challenges** — `2026_02_16_000013`
- `user_id`, `challenge_id`, `assigned_at`, `expires_at`, `claimed_at`
- `progress`, `target`, `status`

**user_bonus_logs** — `2026_02_16_000014`
- `user_id` (unique), `balance` (default 0)

**user_bonus_log_transactions** — `2026_02_16_000015`
- `user_id`, `amount`, `source_type`, `source_id`, `balance_after`, `created_at`

### Subscription & Boost Tables

**group_boosts** — `2026_02_16_000004`
- `group_id`, `user_id` (purchaser), `stripe_subscription_id`
- `status` (active/cancelled/expired), `starts_at`, `ends_at`

**subscriptions** — `2026_02_16_193142` (Cashier standard)
**subscription_items** — `2026_02_16_193143` (Cashier standard)

### Season & Tournament Tables

**seasons** — `2026_02_16_000016`
- `name`, `starts_at`, `ends_at` (dates), `status`

**season_standings** — `2026_02_16_000017`
- `season_id`, `group_id`, `user_id`
- `rating_start`, `rating_end`, `wins`, `losses`, `final_rank`, `reward_tier`

**group_milestones** — `2026_02_16_000018`
- `group_id`, `milestone_slug`, `tier_reached`, `reached_at`, `progress`, `target`

**rivalries** — `2026_02_16_000019`
- `group_id`, `user_1_id`, `user_2_id`
- `user_1_wins`, `user_2_wins`, `total_matches`
- `last_match_at`, `is_active`, `detected_at`

**tournaments** — `2026_02_16_000020`
- `group_id`, `created_by`, `name`, `format`, `bracket_type`
- `max_players`, `min_rating`, `max_rating`, `status`
- `registration_opens_at`, `registration_closes_at`, `starts_at`

**tournament_entries** — `2026_02_16_000021`
- `tournament_id`, `user_id`, `partner_id`, `seed`, `status`

**tournament_rounds** — `2026_02_16_000022`
- `tournament_id`, `round_number`, `bracket`, `match_id`
- `entry_1_id`, `entry_2_id`, `winner_entry_id`, `scheduled_at`

---

## 3. Models & Relationships

### User (`app/Models/User.php`)

**Traits:** Billable (Cashier), HasApiTokens (Sanctum), HasFactory, Notifiable

| Relationship | Type | Target |
|-------------|------|--------|
| `groups()` | BelongsToMany | Group (pivot: role, joined_at) |
| `ownedGroups()` | HasMany | Group (via created_by) |
| `startedSessions()` | HasMany | GameSession (via started_by) |
| `loggedMatches()` | HasMany | GameMatch (via logged_by) |
| `matchPlayers()` | HasMany | MatchPlayer |
| `xpEvents()` | HasMany | UserXpEvent |
| `level()` | HasOne | UserLevel |
| `badges()` | BelongsToMany | Badge (via user_badges) |
| `userBadges()` | HasMany | UserBadge |
| `challenges()` | HasMany | UserChallenge |
| `bonusLogBalance()` | HasOne | UserBonusLog |
| `bonusLogTransactions()` | HasMany | UserBonusLogTransaction |
| `seasonStandings()` | HasMany | SeasonStanding |
| `groupBoosts()` | HasMany | GroupBoost |

### Group (`app/Models/Group.php`)

**Route key:** `slug` (not `id`)

**Boot:** Auto-generates `slug` (name + random chars) and `invite_code` (8-char) on creation.

| Relationship | Type | Target |
|-------------|------|--------|
| `creator()` | BelongsTo | User |
| `members()` | BelongsToMany | User (pivot: role, joined_at) |
| `boosts()` | HasMany | GroupBoost |
| `sessions()` | HasMany | GameSession |
| `matches()` | HasMany | GameMatch |
| `milestones()` | HasMany | GroupMilestone |
| `rivalries()` | HasMany | Rivalry |
| `seasonStandings()` | HasMany | SeasonStanding |
| `tournaments()` | HasMany | Tournament |

**Key Methods:**
- `isMember(User): bool`
- `isOwner(User): bool`
- `isAdminOrOwner(User): bool`
- `getMemberRole(User): ?GroupRole`
- `regenerateInviteCode(): void`
- `transferOwnership(User): void` — transactional, demotes old owner to admin
- `getLongestTenuredMember(): ?User`

### GameSession (`app/Models/GameSession.php`)

| Relationship | Type | Target |
|-------------|------|--------|
| `group()` | BelongsTo | Group |
| `startedBy()` | BelongsTo | User |
| `matches()` | HasMany | GameMatch |

### GameMatch (`app/Models/GameMatch.php`)

**Scopes:** `completed()`, `inGroup(int)`, `playedBetween($from, $to)`

| Relationship | Type | Target |
|-------------|------|--------|
| `group()` | BelongsTo | Group |
| `session()` | BelongsTo | GameSession |
| `loggedBy()` | BelongsTo | User |
| `players()` | HasMany | MatchPlayer |
| `tournamentRound()` | BelongsTo | TournamentRound |

### MatchPlayer (`app/Models/MatchPlayer.php`)

No timestamps. Links users to matches with team (1/2) and position (left/right/solo).

### UserLevel (`app/Models/UserLevel.php`)

One per user. `xpForNextLevel()` returns threshold based on level:

| Level | XP Needed |
|-------|-----------|
| 1→2 | 100 |
| 2→3 | 200 |
| 3→4 | 350 |
| 4→5 | 500 |
| 5→6 | 700 |
| 6→7 | 1000 |
| 7→8 | 1400 |
| 8→9 | 2000 |
| 9→10 | 3000 |
| 10+ | 3000 + (level-9)*500 |

### GroupBoost (`app/Models/GroupBoost.php`)

**Scope:** `active()` — status=Active AND starts_at <= now AND (ends_at null OR ends_at > now)

### Badge (`app/Models/Badge.php`)

Templates stored in `badges` table. `is_secret` hides description until earned.

### Other Models

- **UserXpEvent** — immutable XP log (no updated_at)
- **UserBadge** — earned badges with `is_pinned` (max 3)
- **Challenge** — daily/weekly challenge templates
- **UserChallenge** — progress tracking with status lifecycle
- **UserBonusLog** — currency balance (one per user)
- **UserBonusLogTransaction** — immutable transaction log
- **Season** — competitive seasons with dates
- **SeasonStanding** — per-season rankings
- **GroupMilestone** — group achievement tracking
- **Rivalry** — auto-detected h2h rivalries
- **Tournament**, **TournamentEntry**, **TournamentRound** — bracket tournament system

---

## 4. Enums

All in `app/Enums/`:

| Enum | Values |
|------|--------|
| `GroupRole` | owner, admin, member |
| `MatchFormat` | singles, doubles |
| `MatchStatus` | in_progress, completed, cancelled |
| `SessionStatus` | active, completed |
| `PlayerPosition` | left, right, solo |
| `TeamMode` | random, handpick |
| `BoostStatus` | active, cancelled, expired |
| `BadgeCategory` | wins, volume, social, partnership, secret |
| `BadgeTier` | bronze, silver, gold, platinum |
| `XpSourceType` | match, challenge, badge, bonus |
| `BonusLogSourceType` | challenge_reward, match_spent |
| `ChallengeType` | daily, weekly_xp, weekly_bonus_log |
| `ChallengeDifficulty` | easy, medium, hard |
| `ChallengeStatus` | active, completed, claimed, expired |
| `SeasonStatus` | upcoming, active, completed |
| `RewardTier` | gold, silver, bronze, none |
| `TournamentStatus` | registration, in_progress, completed |
| `TournamentEntryStatus` | registered, checked_in, eliminated, winner |
| `BracketType` | single_elimination, double_elimination, round_robin |
| `TournamentBracketSide` | winners, losers, finals |

---

## 5. Services (Business Logic)

### SubscriptionService (`app/Services/SubscriptionService.php`)

Manages Stripe subscriptions and match access control.

**Key Methods:**

| Method | Purpose |
|--------|---------|
| `getUserStatus(User): array` | Returns `{is_pro, has_any_boost}` |
| `isProSubscriber(User): bool` | Checks `$user->subscribed('pro')` |
| `hasActiveBoost(Group): bool` | Checks `$group->boosts()->active()->exists()` |
| `canLogMatch(User, Group): array` | Returns `{allowed, reason}` — see enforcement order below |
| `getWeeklyMatchCount(Group): int` | Live query, Monday-based, group timezone |
| `createProCheckout(User, interval): string` | Stripe checkout URL |
| `createBoostCheckout(User, Group, interval): string` | Stripe checkout URL |
| `cancelSubscription(User)` | Cancels "pro" subscription |
| `cancelBoost(User, Group)` | Cancels "boost-{group_id}" subscription |
| `resumeSubscription(User)` | Resumes on grace period |
| `resumeBoost(User, Group)` | Resumes on grace period |
| `handleSubscriptionCreated(payload)` | Webhook: activates boost from metadata |
| `handleSubscriptionDeleted(payload)` | Webhook: expires boost |

**Match limit enforcement order** (`canLogMatch`):
1. Group has active boost → **allow**
2. User is Pro → **allow**
3. Weekly count < 5 → **allow**
4. Otherwise → **block** with reason message

**Weekly count:** Computed live via `SELECT COUNT(*) FROM matches WHERE group_id = ? AND played_at >= ?`. Week starts Monday in **group's timezone**. No stored counter — resets naturally.

### XpService (`app/Services/XpService.php`)

Handles XP earning and leveling.

**XP Awards per match:**
| Event | XP |
|-------|-----|
| Playing a match | +20 |
| Winning | +10 |
| Shutout win (opponent scored 0) | +25 |
| First match of the day | +15 |

**Key Methods:**

| Method | Purpose |
|--------|---------|
| `awardMatchXp(GameMatch): array` | Awards XP to all participants, returns `{user_id: {xp_gained, leveled_up, new_level}}` |
| `applyXp(User, int): array` | Applies XP, handles level-ups, returns `{leveled_up, new_level}` |
| `levelName(int): string` (static) | Maps level 1-10 to names |
| `xpProgress(UserLevel): array` (static) | Returns `{current, needed}` for progress bar |

**Level Names:** Beginner, Novice, Rally Ready, Court Regular, Kitchen King, Dink Master, Net Ninja, Court Commander, Grand Dinkster, Pickle Legend

### BadgeService (`app/Services/BadgeService.php`)

Handles badge checking, awarding, and pinning.

**Criteria Types:** wins_total, win_streak, shutouts, comebacks, matches_played, session_matches, h2h_matches, all_partners_in_group, partner_wins, unique_partners_won, pickle_rick, sandwich_pattern, groups_joined

**Key Methods:**

| Method | Purpose |
|--------|---------|
| `checkAfterMatch(GameMatch): array` | Evaluates all badges for match players, returns `{user_id: [{badge_id, name, tier, xp_reward}]}` |
| `checkAfterGroupJoin(User): array` | Checks groups_joined badges |
| `computeProgress(User): array` | Full badge collection with progress for badge index page |
| `awardBadge(User, Badge)` | Creates UserBadge, awards XP if reward exists |
| `pinBadge(User, Badge): array` | Toggles pin (max 3), returns `{pinned, error}` |
| `getPinnedBadges(User): Collection` | Returns up to 3 pinned badges for dashboard |

### MatchService (`app/Services/MatchService.php`)

Orchestrates match creation with XP/badge awards.

| Method | Purpose |
|--------|---------|
| `createSession(Group, User, data): GameSession` | Creates new game session |
| `createMatch(GameSession, User, data): array` | Validates access, creates match + players, awards XP, checks badges. Returns `{match, xp_results, badge_results}` |
| `endSession(GameSession)` | Sets status=completed, finished_at=now |

Throws `MatchLimitExceededException` if `canLogMatch()` returns false.

### StatsService (`app/Services/StatsService.php`)

Complex stat calculations.

| Method | Purpose |
|--------|---------|
| `playerStats(userId, ?groupId, ?from, ?to): array` | Personal stats: games, wins, losses, win_rate, streak, by_format |
| `groupLeaderboard(groupId, ?from, ?to): array` | Ranked group members with stats |
| `headToHead(groupId, user1, user2, ?from, ?to): array` | H2H stats + recent matches |
| `partnerships(groupId, ?from, ?to): array` | Doubles partnership stats |
| `bestPartner(userId, groupId, ?from, ?to): ?array` | Best partner (min 3 games) |
| `dashboardStats(userId): array` | Last 30 days stats |

### RotationService (`app/Services/RotationService.php`)

Team assignment logic for sessions.

| Method | Purpose |
|--------|---------|
| `getNextAssignment(GameSession): array` | Returns `{team_1, team_2, sitting_out}` |

**Random mode algorithm:**
1. Count games per player
2. Select players with fewest games (fair rotation)
3. Generate team split not yet used
4. Fallback to random if all splits exhausted

---

## 6. Controllers & Routes

### Web Routes (`routes/web.php`)

#### Public Routes

| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/` | Closure → Welcome | — |
| GET | `/pricing` | Closure → Pricing | `pricing` |
| GET | `/join/{code}` | GroupInviteController@show | `invite.show` |

#### Auth Required Routes

**Dashboard:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/dashboard` | Closure (verified) | `dashboard` |

**Profile:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/profile` | ProfileController@edit | `profile.edit` |
| PATCH | `/profile` | ProfileController@update | `profile.update` |
| DELETE | `/profile` | ProfileController@destroy | `profile.destroy` |

**Groups:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/groups` | GroupController@index | `groups.index` |
| GET | `/groups/create` | GroupController@create | `groups.create` |
| POST | `/groups` | GroupController@store | `groups.store` |
| GET | `/groups/{group}` | GroupController@show | `groups.show` |
| GET | `/groups/{group}/settings` | GroupController@edit | `groups.edit` |
| PATCH | `/groups/{group}` | GroupController@update | `groups.update` |
| DELETE | `/groups/{group}` | GroupController@destroy | `groups.destroy` |

**Group Members:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| DELETE | `/groups/{group}/leave` | GroupMemberController@leave | `groups.leave` |
| DELETE | `/groups/{group}/members/{user}` | GroupMemberController@destroy | `groups.members.destroy` |
| POST | `/groups/{group}/members/{user}/promote` | GroupMemberController@promote | `groups.members.promote` |
| POST | `/groups/{group}/transfer` | GroupMemberController@transfer | `groups.transfer` |

**Group Invites:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| POST | `/groups/{group}/invite/regenerate` | GroupInviteController@regenerate | `groups.invite.regenerate` |
| POST | `/join/{code}` | GroupInviteController@join | `groups.join` |

**Stats:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/groups/{group}/stats` | StatsController@groupStats | `groups.stats` |
| GET | `/groups/{group}/stats/head-to-head` | StatsController@headToHead | `groups.stats.h2h` |
| GET | `/groups/{group}/stats/partnerships` | StatsController@partnerships | `groups.stats.partnerships` |
| GET | `/groups/{group}/stats/players/{user}` | StatsController@playerDetail | `groups.stats.player` |

**Billing:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/billing` | BillingController@index | `billing.index` |
| POST | `/billing/checkout` | BillingController@checkout | `billing.checkout` |
| POST | `/billing/boost/{group}` | BillingController@boostCheckout | `billing.boost-checkout` |
| GET | `/billing/success` | BillingController@success | `billing.success` |
| POST | `/billing/portal` | BillingController@portal | `billing.portal` |
| DELETE | `/billing/cancel-pro` | BillingController@cancelPro | `billing.cancel-pro` |
| DELETE | `/billing/cancel-boost/{group}` | BillingController@cancelBoost | `billing.cancel-boost` |
| POST | `/billing/resume-pro` | BillingController@resumePro | `billing.resume-pro` |
| POST | `/billing/resume-boost/{group}` | BillingController@resumeBoost | `billing.resume-boost` |

**Badges:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/badges` | BadgeController@index | `badges.index` |
| PUT | `/badges/{badge}/pin` | BadgeController@pin | `badges.pin` |

**Play:**
| Method | Path | Handler | Name |
|--------|------|---------|------|
| GET | `/play` | PlayController@index | `play.index` |
| GET | `/groups/{group}/play` | PlayController@setup | `play.setup` |
| POST | `/groups/{group}/play` | PlayController@createSession | `play.create-session` |
| GET | `/groups/{group}/play/{session}` | PlayController@session | `play.session` |
| POST | `/groups/{group}/play/{session}/matches` | PlayController@storeMatch | `play.store-match` |
| POST | `/groups/{group}/play/{session}/end` | PlayController@endSession | `play.end-session` |

### API Routes (`routes/api.php`)

Most API controllers return **501 Not Implemented** except:

| Method | Path | Handler | Status |
|--------|------|---------|--------|
| GET | `/api/subscription` | Api\SubscriptionController@show | Implemented |
| POST | `/api/subscription` | Api\SubscriptionController@create | Implemented |
| DELETE | `/api/subscription` | Api\SubscriptionController@cancel | Implemented |
| POST | `/api/subscription/boost/{group}` | Api\SubscriptionController@boost | Implemented |
| POST | `/api/stripe/webhook` | Api\WebhookController@handleWebhook | Implemented |

---

## 7. Policies & Authorization

### GroupPolicy (`app/Policies/GroupPolicy.php`)

| Method | Check | Used By |
|--------|-------|---------|
| `view(User, Group)` | User is group member | show, stats, play |
| `update(User, Group)` | User is admin or owner | edit, update, regenerate invite |
| `delete(User, Group)` | User is owner | destroy |
| `manageMembers(User, Group)` | User is admin or owner | remove members |
| `transferOwnership(User, Group)` | User is owner | transfer, promote |

---

## 8. Form Requests (Validation)

### StoreGroupRequest
- `name`: required, string, min:3, max:100
- `timezone`: nullable, string, timezone:all

### CreateSessionRequest
- `format`: required, MatchFormat enum
- `team_mode`: required, TeamMode enum
- `player_ids`: required, array, min:2 (min:4 for doubles)
- All player_ids must be group members

### StoreMatchRequest
- `team_1_score`: required, integer, 0-99
- `team_2_score`: required, integer, 0-99 (cannot tie)
- `players`: required, array, 2-4 entries
- Each player: `user_id`, `team` (1 or 2), `position` (PlayerPosition enum)
- Each team must have correct player count (1 for singles, 2 for doubles)

### ProfileUpdateRequest
- `name`: required, string, max:255
- `email`: required, string, lowercase, email, max:255, unique (ignore self)

---

## 9. Middleware

### HandleInertiaRequests (`app/Http/Middleware/HandleInertiaRequests.php`)

Shares global data on every Inertia request:

```
auth: {
  user: User | null,
  level: {
    current_level, total_xp, xp_for_next,
    xp_progress: { current, needed },
    level_name
  } | null,
  pinned_badges: [...] (lazy-loaded)
},
subscription: {
  is_pro: bool,
  has_any_boost: bool
} | null,
flash: {
  success, error,
  xp_awarded: { user_id: { xp_gained, leveled_up, new_level } } | null,
  badges_earned: { user_id: [{ badge_id, name, tier, xp_reward }] } | null
}
```

---

## 10. Frontend — Layouts & Components

### Layouts

**AuthenticatedLayout** (`resources/js/Layouts/AuthenticatedLayout.vue`)
- Top nav: logo, Dashboard/Groups/Badges links
- User dropdown: name, level badge, Profile/Billing/Logout
- Mobile hamburger menu
- Optional `backHref` prop for back button in header
- `#header` slot for page title

**GuestLayout** (`resources/js/Layouts/GuestLayout.vue`)
- Centered card on gray background with logo

### UI Components

| Component | File | Purpose |
|-----------|------|---------|
| `ApplicationLogo` | Components/ApplicationLogo.vue | Green logo with dark text |
| `ApplicationLogoDark` | Components/ApplicationLogoDark.vue | Logo with white text |
| `PrimaryButton` | Components/PrimaryButton.vue | Dark gray button (bg-gray-800) |
| `SecondaryButton` | Components/SecondaryButton.vue | White outlined button |
| `DangerButton` | Components/DangerButton.vue | Red button |
| `TextInput` | Components/TextInput.vue | Styled text input |
| `InputLabel` | Components/InputLabel.vue | Form label |
| `InputError` | Components/InputError.vue | Red error text |
| `Checkbox` | Components/Checkbox.vue | Indigo checkbox |
| `Dropdown` | Components/Dropdown.vue | Click-to-open dropdown |
| `DropdownLink` | Components/DropdownLink.vue | Link inside dropdown |
| `NavLink` | Components/NavLink.vue | Nav link with active state |
| `ResponsiveNavLink` | Components/ResponsiveNavLink.vue | Mobile nav link |
| `Modal` | Components/Modal.vue | Dialog with backdrop |
| `ConfirmationModal` | Components/ConfirmationModal.vue | Confirm/Cancel dialog |
| `RoleBadge` | Components/RoleBadge.vue | owner/admin/member pill |
| `UpgradePrompt` | Components/UpgradePrompt.vue | Amber match limit warning |

### Play Components

| Component | File | Purpose |
|-----------|------|---------|
| `PlayerSelector` | Components/Play/PlayerSelector.vue | Grid of clickable player buttons |
| `FormatSelector` | Components/Play/FormatSelector.vue | Singles/Doubles picker |
| `TeamModeSelector` | Components/Play/TeamModeSelector.vue | Random/Handpick picker |
| `CourtView` | Components/Play/CourtView.vue | SVG court with team slots + scoring |
| `MatchHistoryCard` | Components/Play/MatchHistoryCard.vue | Compact match result display |
| `ScoreInput` | Components/Play/ScoreInput.vue | +/- score buttons (0-99) |

### Stats Components

| Component | File | Purpose |
|-----------|------|---------|
| `StatCard` | Components/Stats/StatCard.vue | Single stat display |
| `LeaderboardTable` | Components/Stats/LeaderboardTable.vue | Sortable leaderboard |
| `PartnershipTable` | Components/Stats/PartnershipTable.vue | Partnership stats table |
| `TimeRangeSelector` | Components/Stats/TimeRangeSelector.vue | Daily/Weekly/Monthly/All picker |

---

## 11. Frontend — Pages

### Public Pages

**Welcome** (`Pages/Welcome.vue`)
- Hero: "Track Every Point. Settle Every Score."
- Features grid (4 items), How It Works (3 steps), Social proof stats
- Standalone layout (no wrapper)

**Pricing** (`Pages/Pricing.vue`)
- 3-column card grid: Free ($0), Pro ($5/mo annual), Group Boost ($10.83/mo annual)
- Dark nav, dark header, cards overlap via -mt-20
- Standalone layout with min-h-screen flex wrapper

### Auth Pages (all use GuestLayout)

- `Pages/Auth/Login.vue` — email/password/remember
- `Pages/Auth/Register.vue` — name/email/password/confirm
- `Pages/Auth/ForgotPassword.vue` — email
- `Pages/Auth/ResetPassword.vue` — token/email/password/confirm
- `Pages/Auth/ConfirmPassword.vue` — password
- `Pages/Auth/VerifyEmail.vue` — resend link

### Dashboard (`Pages/Dashboard.vue`)

**Props:** groups, personalStats, levelData, pinnedBadges

**Sections:**
1. XP/Level card with progress bar
2. Pinned badges (up to 3)
3. Personal stats (30 days): Games, Record, Win Rate, Streak
4. Pro upsell footnote (if not Pro and has games)
5. My Groups list

### Group Pages

**Groups/Index** — Grid of group cards with member count and role badge

**Groups/Create** — Name + timezone form

**Groups/Show** — Props: group, members, userRole, leaderboardPreview, myStats, hasBoost, weeklyMatchCount
- Invite card (admin/owner)
- Members list with promote/remove actions
- Leaderboard preview (top 5)
- Match counter + upgrade link (if free)

**Groups/Edit** — Group settings + danger zone (transfer/delete)

**Groups/Join** — Invite preview for guests/members/non-members

### Stats Pages

**Stats/Leaderboard** — Sortable table with time range selector, "All Time" locked behind Pro/Boost

**Stats/HeadToHead** — Two player dropdowns, stat cards, by-format breakdown, recent matches

**Stats/Partnerships** — Partnership table with win rates

**Stats/PlayerDetail** — Overview cards (6), by-format breakdown, best partner

### Play Pages

**Play/SelectGroup** — List of groups to start playing

**Play/Setup** — 3-step wizard: Select Players → Format → Team Mode

**Play/Session** — Props: group, session, players, matches, nextAssignment, canLogMatch, hasBoost, weeklyMatchCount
- Two phases: Court (team assignment) → Scoring (enter scores)
- XP toast, badge toast on match save
- Match history, session summary on completion
- Match counter pill (free users)

### Billing Pages

**Billing/Index** — Pro status + group boost management. Monthly/yearly toggles, cancel/resume actions.

**Billing/Success** — Green checkmark confirmation page.

### Badge Page

**Badges/Index** — All badges grouped by category. Progress bars for unearned, pin toggle for earned (max 3).

---

## 12. Authentication Flow

1. User registers/logs in via Breeze session auth
2. `HandleInertiaRequests` middleware shares `auth.user` + `auth.level` on every request
3. `AuthenticatedLayout` reads `$page.props.auth.user` for nav display
4. Protected routes use `auth` middleware (applied in `routes/web.php`)
5. Dashboard requires `verified` middleware additionally
6. API routes use `auth:sanctum` middleware

---

## 13. Subscription & Billing System

### Subscription Types

| Name | Stripe Key | Price Env Vars | What It Does |
|------|-----------|----------------|--------------|
| Pro | `pro` | `STRIPE_PRO_MONTHLY_PRICE`, `STRIPE_PRO_YEARLY_PRICE` | Unlimited personal logging, all-time stats |
| Group Boost | `boost-{group_id}` | `STRIPE_BOOST_MONTHLY_PRICE`, `STRIPE_BOOST_YEARLY_PRICE` | Unlimited for entire group |

Pro and Boost are **independent** — not tiers. A user can have both.

### Checkout Flow

1. User clicks upgrade on `/billing`
2. `BillingController@checkout` creates Stripe checkout session via `SubscriptionService`
3. Redirects to Stripe hosted checkout
4. On success → Stripe redirects to `/billing/success`
5. Webhook fires `customer.subscription.created`
6. `WebhookController` → `SubscriptionService::handleSubscriptionCreated()` → creates/updates `GroupBoost` record

### Webhook Handler

**File:** `app/Http/Controllers/Api/WebhookController.php`

Extends Cashier's base webhook controller. Listens for:
- `customer.subscription.created/updated` → `handleSubscriptionCreated()` (activates boost)
- `customer.subscription.deleted` → `handleSubscriptionDeleted()` (expires boost)

### Free Tier Limits

- 5 matches/week per group (Monday reset, group timezone)
- Stats limited to daily/weekly/monthly (no all-time)
- Count is a live DB query, not stored

### Where Limits Are Referenced

| File | What |
|------|------|
| `SubscriptionService.php:49` | `if ($weeklyCount < 5)` |
| `SubscriptionService.php:58` | Error message |
| `UpgradePrompt.vue:9` | Default message |
| `Billing/Index.vue` | Counter display |
| `Groups/Show.vue:195` | Counter display |
| `Play/Session.vue:375` | Counter pill |

---

## 14. Match & Session System

### Session Lifecycle

1. **Setup** — User selects players, format (singles/doubles), team mode (random/handpick)
2. **Create** — `MatchService::createSession()` → status=Active
3. **Play** — Court phase (assign teams) → Scoring phase (enter scores)
4. **Save Match** — `MatchService::createMatch()` → creates GameMatch + MatchPlayers, awards XP, checks badges
5. **Next Game** — `RotationService::getNextAssignment()` provides new team split
6. **End** — `MatchService::endSession()` → status=Completed, finished_at=now

### Match Creation (in transaction)

1. Check `canLogMatch()` — throws `MatchLimitExceededException` if blocked
2. Determine winning_team from scores (higher score wins)
3. Create `GameMatch` record
4. Create `MatchPlayer` records (one per player)
5. Award XP via `XpService::awardMatchXp()`
6. Check badges via `BadgeService::checkAfterMatch()`
7. Return `{match, xp_results, badge_results}`

### Team Rotation (Random Mode)

1. Count games per player for fair distribution
2. Select players with fewest games
3. Generate team combination not yet used in session
4. Fallback to random if all combinations exhausted
5. Sitting out players tracked separately

---

## 15. Gamification System (XP, Badges)

### XP Earning

All XP events logged to `user_xp_events` (immutable).

| Source | XP | When |
|--------|-----|------|
| Play match | +20 | Every completed match |
| Win match | +10 | Match winners |
| Shutout win | +25 | Winner when opponent scores 0 |
| First match of day | +15 | Player's first match today |
| Badge earned | varies | Badge's `xp_reward` value |

### Level System

10 named levels. Levels 10+ scale with formula `3000 + (level-9)*500`.

Progress bar: `UserLevel.total_xp` tracked cumulatively. `xpProgress()` calculates current/needed within level.

### Badge System

Badges stored as templates in `badges` table. Seeded by `BadgeSeeder`.

**Criteria evaluated after each match:**
- Total wins, win streak, shutouts, comebacks
- Matches played, session matches, h2h matches
- Partner-related (all partners in group, partner wins, unique partners won)
- Pattern-based (pickle_rick: 3+ losses then 3+ wins; sandwich: WLWLW)
- Social (groups joined — checked after join, not match)

**Pinning:** Max 3 pinned badges per user. Displayed on dashboard. Toggle via `BadgeService::pinBadge()`.

---

## 16. Stats System

### Time Range Access Control

| User Type | Available Ranges |
|-----------|-----------------|
| Free | Daily, Weekly, Monthly |
| Pro | Daily, Weekly, Monthly, **All Time** |
| Group with Boost | Daily, Weekly, Monthly, **All Time** (for that group) |

Enforced in `StatsController` — restricts range to max `monthly` if user can't view all-time.

### Available Stats

**Leaderboard** (per group): Rank, games, wins, losses, win rate, streak, point diff

**Head-to-Head**: Total games, each player's wins, point totals, by-format breakdown, recent 10 matches

**Partnerships** (doubles only): Games, wins, losses, win rate, avg point diff

**Player Detail**: Overview stats + by-format breakdown + best partner (min 3 games)

**Dashboard**: Last 30 days personal stats

---

## 17. Group Management

### Role Hierarchy

| Role | Can | Cannot |
|------|-----|--------|
| Owner | Everything | — |
| Admin | Edit group, manage members (remove members), regenerate invite | Delete group, transfer ownership, remove admins |
| Member | View, play, stats | Manage anything |

### Invite System

- Each group has a unique 8-char `invite_code`
- Invite URL: `{origin}/join/{code}`
- Public preview page (guest or auth)
- Joining attaches user as Member with `joined_at=now`
- Admins/owners can regenerate invite code

### Ownership Transfer

- Owner can transfer to any group member
- Old owner demoted to Admin
- Runs in DB transaction
- On owner leave: auto-transfers to longest-tenured member
- If last member: group is deleted

---

## 18. Key Data Flows

### Match → XP → Level → Badge Flow

```
User clicks "Save & Next Game"
  → PlayController@storeMatch
    → MatchService::createMatch()
      → SubscriptionService::canLogMatch() ← access check
      → DB transaction: GameMatch + MatchPlayers
      → XpService::awardMatchXp()
        → +20 per player, +10 winners, +25 shutout, +15 first-of-day
        → UserXpEvent created per award
        → UserLevel.total_xp updated, level-ups processed
      → BadgeService::checkAfterMatch()
        → Evaluates all criteria for each player
        → Awards badges + bonus XP
    → Flash: xp_awarded, badges_earned
  → Redirect back to session
  → Session.vue watches flash data → shows XP toast, badge toast
```

### Subscription Check Flow

```
User logs match
  → MatchService::createMatch()
    → SubscriptionService::canLogMatch(user, group)
      1. group->boosts()->active()->exists() ? → allow
      2. user->subscribed('pro') ? → allow
      3. getWeeklyMatchCount(group) < 5 ? → allow
      4. else → MatchLimitExceededException
```

### Inertia Shared Props Flow

```
Every request → HandleInertiaRequests middleware
  → auth.user (User model)
  → auth.level (UserLevel + computed progress)
  → auth.pinned_badges (lazy-loaded)
  → subscription.is_pro, subscription.has_any_boost
  → flash.success, flash.error, flash.xp_awarded, flash.badges_earned
```

---

## 19. Dev Commands & Testing

### Artisan Commands (in `routes/console.php`)

```bash
# Fill a group's weekly matches to max (5/5)
./vendor/bin/sail artisan dev:max-matches {group_slug}

# Reset a group's weekly matches to 0/5 (backdates, doesn't delete)
./vendor/bin/sail artisan dev:reset-matches {group_slug}
```

### Useful Commands

```bash
./vendor/bin/sail artisan migrate              # Run migrations
./vendor/bin/sail artisan migrate:fresh --seed  # Fresh DB with seed data
./vendor/bin/sail artisan tinker               # REPL
./vendor/bin/sail artisan test                 # Run tests
./vendor/bin/sail artisan optimize:clear       # Clear caches
```

### Seeders (run order)

1. BadgeSeeder
2. ChallengeSeeder
3. SeasonSeeder
4. UserSeeder
5. GroupSeeder
6. GameSessionSeeder
7. GamificationSeeder
8. RivalrySeeder

---

## 20. Session Changes Log

Changes made during the Feb 17 2026 development session:

1. **Weekly match limit lowered** from 10 to 5 per group
2. **Dashboard Pro upsell** — footnote below stats card for non-Pro users
3. **Groups/Show match counter** — weekly count + upgrade link below leaderboard
4. **Play/Session match counter pill** — informational counter during active sessions
5. **Play button color** — changed to emerald green on Dashboard and Groups/Show
6. **End session modal bug fix** — added `onSuccess` callback to close modal
7. **Pricing page** — new `/pricing` route with 3-column comparison cards
8. **Dev artisan commands** — `dev:max-matches` and `dev:reset-matches`

For detailed file-by-file breakdown of these changes, see `SESSION_DOCS.md`.

---

## 21. Debugging Guide

### "Match limit not working"
1. Check `SubscriptionService::canLogMatch()` enforcement order
2. Verify group timezone in DB (`groups.timezone`)
3. Run: `SELECT COUNT(*) FROM matches WHERE group_id = ? AND played_at >= ?`
4. Pro users bypass the limit — check `subscriptions` table for 'pro' type

### "XP not awarded"
1. Trace `XpService::awardMatchXp()` — checks match players via `$match->players`
2. Check `user_xp_events` table for the match's `source_id`
3. Check `user_levels` table for current level/total_xp
4. Level-up threshold: see UserLevel model's `xpForNextLevel()`

### "Badge not earning"
1. Check `badges` table for criteria_type and criteria_value
2. Trace `BadgeService::checkAfterMatch()` → `computeCriteriaValue()`
3. Check `user_badges` table — badge may already be earned
4. Secret badges: `is_secret=true` hides until earned but doesn't affect earning

### "Stats showing wrong data"
1. Check time range — free users max out at `monthly`
2. All stats use `completed` matches only (`MatchStatus::Completed`)
3. Verify player is in `match_players` table for those matches
4. Leaderboard sorted by win_rate desc, then games desc

### "Stripe webhook failing"
1. Check webhook secret matches `STRIPE_WEBHOOK_SECRET` env
2. Webhook URL: `POST /api/stripe/webhook`
3. Check `WebhookController` extends Cashier's base controller
4. For boost: metadata must include `group_id` in Stripe checkout session
5. Check `group_boosts` table for boost status

### "CTA not showing"
- **Dashboard:** Needs `personalStats.games > 0` AND `!isPro`
- **Groups/Show:** Needs `!hasBoost` AND `!isPro`
- **Play/Session:** Needs `!hasBoost` AND `!isPro` AND session is `active`
- All read `subscription.is_pro` from `HandleInertiaRequests` shared props

### "Group route 404"
- Groups use `slug` as route key (not `id`)
- Check `Group::getRouteKeyName()` returns `'slug'`
- Slugs are auto-generated on creation and are unique

### "Session not ending"
- `endSessionOnly()` in Session.vue must have `onSuccess` callback
- Server: `MatchService::endSession()` sets status=Completed
- Session must belong to the group (controller validates this)
