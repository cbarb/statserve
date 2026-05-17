# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

StatServe is a pickleball stats tracking app. Groups of players log matches, earn XP/badges, and view leaderboards. Pro users ($5/mo) get unlimited match logging and all-time stats. Group Boosts ($10.83/mo) unlock those same perks for an entire group. Public tournaments with bracket generation are also supported.

## Commands

```bash
# Start everything (Sail + Vite HMR)
npm run start

# Or separately
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev

# Production build
npm run build

# Database
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed

# Tests
./vendor/bin/sail artisan test
./vendor/bin/sail artisan test --testsuite=Unit
./vendor/bin/sail artisan test --filter=TestClassName

# Dev helpers
./vendor/bin/sail artisan dev:max-matches {group_slug}   # fills weekly limit to 5/5
./vendor/bin/sail artisan dev:reset-matches {group_slug}  # resets weekly count to 0/5

# Queue worker (required for emails/notifications to actually send)
./vendor/bin/sail artisan queue:work

# Cache
./vendor/bin/sail artisan optimize:clear
```

Testing uses an in-memory SQLite-like setup (`DB_DATABASE=testing`), array cache, sync queues — no Docker needed for tests.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Vue 3 + Inertia.js |
| Styling | Tailwind CSS |
| Database | MySQL 8.4 (Docker via Sail) |
| Cache/Queue/Sessions | Redis (Docker) |
| Auth | Laravel Breeze (session-based) |
| Billing | Laravel Cashier (Stripe) |
| API Tokens | Laravel Sanctum |
| Email | Resend (prod), Mailpit (local at :8025) |
| Geocoding | Nominatim/OpenStreetMap (no API key needed) |
| JS Routes | Ziggy (`tightenco/ziggy`) |

## Architecture Overview

### Request Flow (Web)

All web pages use **Inertia.js** — the backend returns Inertia responses (not API JSON), and Vue renders them client-side. `HandleInertiaRequests` middleware injects shared props on every request: `auth.user`, `auth.level`, `auth.pinned_badges`, `subscription.{is_pro,has_any_boost}`, and `flash.{success,error,xp_awarded,badges_earned}`.

Groups are identified by `slug` (not `id`) in all routes. `Group::getRouteKeyName()` returns `'slug'`.

### Service Layer

Business logic lives in `app/Services/`, not controllers:

- **MatchService** — orchestrates match creation: checks subscription limits → DB transaction → awards XP → checks badges → returns `{match, xp_results, badge_results}`
- **SubscriptionService** — all match limit enforcement. Order: group boost active → user is Pro → weekly count < 5 → block. Weekly count is a live DB query (Monday-based, group timezone), never a stored counter.
- **XpService** — XP events are immutable (`user_xp_events`). Awards: +20 play, +10 win, +25 shutout, +15 first-of-day.
- **BadgeService** — criteria evaluated after every match. Also checks `groups_joined` after group join.
- **StatsService** — all stat calculations (leaderboard, H2H, partnerships, player detail).
- **RotationService** — fair team assignment for sessions (fewest-games-first rotation).
- **TournamentService** — tournament lifecycle, location search (Haversine + text), join/leave, Stripe checkout for entry fees.
- **BracketService** — single elimination (power-of-2 with byes), double elimination (winners + losers + grand finals), round robin.
- **GeocodingService** — Nominatim lookups, cached 24h in Redis.

### Match Limit Enforcement

Free users get 5 matches/week per group. Enforced in `SubscriptionService::canLogMatch()` called from `MatchService::createMatch()`. Throws `MatchLimitExceededException` when blocked. The `canLogMatch` check order is important: **group boost beats everything**, then Pro, then count check.

### Subscription Types

Two independent subscription products (not tiers):
- `pro` — personal, unlocks unlimited logging + all-time stats
- `boost-{group_id}` — per-group, unlocks those perks for every member

### Tournament Entry Fees

Entry fees stored in **cents** (e.g., `1000` = $10.00). Free tournaments use `TournamentService::join()` directly. Paid tournaments redirect to Stripe Checkout; entry is created in `completeEntryPayment()` triggered by the `checkout.session.completed` webhook. Refunds issued automatically on `leave()` if `stripe_payment_intent_id` exists.

### Authorization

Policies in `app/Policies/`:
- **GroupPolicy** — `view` requires membership; `update`/`manageMembers` requires admin/owner; `delete`/`transferOwnership` requires owner.
- **TournamentPolicy** — `create` requires Pro subscription; public tournaments are viewable by any auth user.

### Frontend Patterns

- Pages receive all data as Inertia props (no client-side API calls for initial data).
- `$page.props.auth`, `$page.props.subscription`, `$page.props.flash` are globally available via `HandleInertiaRequests`.
- XP and badge toasts in `Play/Session.vue` watch `flash.xp_awarded` and `flash.badges_earned`.
- Pagination text uses `{{ }}` interpolation (not `v-html`) for XSS safety.
- `@/*` aliases to `resources/js/*` (set in `jsconfig.json`).

### Notifications

All notifications implement `ShouldQueue` — they go through Redis queue. For local dev, run `./vendor/bin/sail artisan queue:work` and check Mailpit at `localhost:8025`. Tournament reminder emails are sent hourly via the scheduler (`bootstrap/app.php`).

### Key Non-Obvious Details

- `GROUP_USER` pivot stores `role` (owner/admin/member) and `joined_at`. Ownership transfer runs in a DB transaction and demotes the old owner to admin.
- Stats with `range=all_time` are server-enforced to `monthly` for non-Pro users — frontend time range selector is just UI.
- `UserXpEvent` and `UserBonusLogTransaction` have no `updated_at` (immutable logs).
- The `MatchFormat` enum has both legacy values (`singles`, `doubles` for game sessions) and tournament-specific values (`mens_singles`, `open_doubles`, etc. — 7 total). The `format` column is VARCHAR(30).
- `Tournament.registeredCount()` counts doubles entries as 2 slots toward `max_players`.
- `PROJECT_DOCS.md` is the comprehensive reference for schema, all routes, all service methods, and debugging guides — read it for deep dives.
