# Session Documentation — Billing CTAs, Limits, Pricing & Bug Fixes

This document covers every change made in the Feb 17 2026 session. Each section maps a feature to the exact files, lines, and logic involved so you can trace and fix any issue.

---

## Table of Contents

1. [Weekly Match Limit (5/week)](#1-weekly-match-limit-5week)
2. [Billing CTAs — Dashboard Pro Upsell](#2-billing-ctas--dashboard-pro-upsell)
3. [Billing CTAs — Groups/Show Match Counter](#3-billing-ctas--groupsshow-match-counter)
4. [Billing CTAs — Play/Session Match Counter Pill](#4-billing-ctas--playsession-match-counter-pill)
5. [Play Button Color (Emerald Green)](#5-play-button-color-emerald-green)
6. [End Session Modal Bug Fix](#6-end-session-modal-bug-fix)
7. [Pricing Page (/pricing)](#7-pricing-page-pricing)
8. [Dev Artisan Commands](#8-dev-artisan-commands)
9. [File Index — Every File Modified](#9-file-index--every-file-modified)

---

## 1. Weekly Match Limit (5/week)

**What changed:** The free tier weekly match limit was lowered from 10 to 5 per group.

### How the limit works

There is **no stored counter** in the database. The count is computed on every request by querying the `matches` table:

**File:** `app/Services/SubscriptionService.php` — `getWeeklyMatchCount()` (line 62)

```php
public function getWeeklyMatchCount(Group $group): int
{
    $tz = $group->timezone ?? 'UTC';
    $weekStart = Carbon::now($tz)->startOfWeek(Carbon::MONDAY)->utc();

    return GameMatch::where('group_id', $group->id)
        ->where('played_at', '>=', $weekStart)
        ->count();
}
```

- Uses the **group's timezone** to determine when Monday starts
- Converts to UTC for the DB query
- Counts all `matches` rows with `played_at >= weekStart`
- Resets naturally every Monday — no cron needed

### Enforcement

**File:** `app/Services/SubscriptionService.php` — `canLogMatch()` (line 35)

The check order is:
1. Group has active boost? → **allow** (skip count entirely)
2. User is Pro subscriber? → **allow** (doesn't count against group pool)
3. Weekly count < **5**? → **allow**
4. Otherwise → **block** with error message

The blocking message (line 58):
> "This group has reached its weekly limit of 5 free matches. Upgrade to Pro or boost this group for unlimited matches."

### All files where "5" or the limit is referenced

| File | What |
|------|------|
| `app/Services/SubscriptionService.php:49` | `if ($weeklyCount < 5)` — enforcement |
| `app/Services/SubscriptionService.php:58` | Error message string |
| `resources/js/Components/UpgradePrompt.vue:9` | Default prop message |
| `resources/js/Pages/Billing/Index.vue:193` | `X/5 free matches this week` |
| `resources/js/Pages/Groups/Show.vue:195` | `X/5 free matches this week` |
| `resources/js/Pages/Play/Session.vue:375` | `X/5 free matches this week` |
| `PRODUCT_SPEC.md` | Lines 35, 57-59, 461, 469, 516-517 |

### Amber warning threshold

The counter text turns amber (warning color) when matches are **>= 4** (i.e. 4/5 or 5/5):
- `Groups/Show.vue:195` — `weeklyMatchCount >= 4 ? 'text-amber-600' : 'text-gray-500'`
- `Play/Session.vue:373` — `weeklyMatchCount >= 4 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'`

---

## 2. Billing CTAs — Dashboard Pro Upsell

**Purpose:** Show free users a subtle footnote below their stats card nudging them to upgrade to Pro for all-time stats.

### Visibility conditions

Shows when **both** are true:
- `personalStats.games > 0` (user has played games)
- `!isPro` (user does not have a Pro subscription)

### Data source

`isPro` is read from the globally shared Inertia props:

**File:** `resources/js/Pages/Dashboard.vue` (lines 6, 22-23)
```js
import { Head, Link, usePage } from '@inertiajs/vue3';
// ...
const page = usePage();
const isPro = page.props.subscription?.is_pro;
```

The `subscription.is_pro` prop is set in:

**File:** `app/Http/Middleware/HandleInertiaRequests.php` (line 52)
```php
'subscription' => $user ? $subscriptionService->getUserStatus($user) : null,
```

Which calls `SubscriptionService::getUserStatus()` (line 15) → `isProSubscriber()` (line 25) → `$user->subscribed('pro')`.

### Template

**File:** `resources/js/Pages/Dashboard.vue` (lines 120-123)
```html
<p v-if="personalStats.games > 0 && !isPro" class="px-2 text-sm text-gray-500">
    Your stats are limited to 30 days.
    <Link :href="route('billing.index')" class="text-emerald-600 hover:text-emerald-500">
        Unlock all-time stats with Pro &rarr;
    </Link>
</p>
```

- Positioned directly below the Personal Stats `</div>` (after line 119), outside the card — it's a standalone `<p>` footnote, not inside a card
- Links to `/billing`
- Styled: gray text with emerald link

---

## 3. Billing CTAs — Groups/Show Match Counter

**Purpose:** Below the leaderboard on a group page, show the weekly match count and an upgrade link for unboosted groups.

### Backend — data passed from controller

**File:** `app/Http/Controllers/GroupController.php` (lines 90-92, 107-108)

```php
$subscriptionService = app(SubscriptionService::class);
$hasBoost = $subscriptionService->hasActiveBoost($group);
$weeklyMatchCount = $hasBoost ? 0 : $subscriptionService->getWeeklyMatchCount($group);
```

Two new Inertia props added to `Groups/Show`:
- `hasBoost` (bool) — whether the group has an active boost subscription
- `weeklyMatchCount` (int) — current week's match count (0 if boosted, to skip the DB query)

**How `hasActiveBoost` works:** `SubscriptionService::hasActiveBoost()` (line 30) checks `$group->boosts()->active()->exists()`. The `active()` scope is on the `GroupBoost` model and filters by `BoostStatus::Active`.

### Frontend — visibility and rendering

**File:** `resources/js/Pages/Groups/Show.vue`

Props (lines 17-18):
```js
hasBoost: Boolean,
weeklyMatchCount: Number,
```

Computed visibility (lines 24-26):
```js
const page = usePage();
const isPro = page.props.subscription?.is_pro;
const showMatchCounter = !props.hasBoost && !isPro;
```

Shows when **both** are true:
- Group does NOT have an active boost
- Current user is NOT a Pro subscriber

Template (lines 194-198):
```html
<p v-if="showMatchCounter" class="px-2 text-sm">
    <span :class="weeklyMatchCount >= 4 ? 'text-amber-600' : 'text-gray-500'">
        {{ weeklyMatchCount }}/5 free matches this week
    </span>
    <span class="mx-1 text-gray-300">&middot;</span>
    <Link :href="route('billing.index')" class="text-emerald-600 hover:text-emerald-500">
        Unlock unlimited &rarr;
    </Link>
</p>
```

- Positioned below the leaderboard card, outside the card
- Count turns amber at >= 4
- Links to `/billing`

---

## 4. Billing CTAs — Play/Session Match Counter Pill

**Purpose:** During an active play session, show a centered pill with the weekly match count. No upgrade link — just awareness.

### Backend — data passed from controller

**File:** `app/Http/Controllers/PlayController.php` (lines 133-134, 154-155)

```php
$hasBoost = $this->subscriptionService->hasActiveBoost($group);
$weeklyMatchCount = $hasBoost ? 0 : $this->subscriptionService->getWeeklyMatchCount($group);
```

Same pattern as GroupController. Uses the already-injected `$this->subscriptionService` (constructor injection, line 23).

### Frontend — visibility and rendering

**File:** `resources/js/Pages/Play/Session.vue`

Props (lines 23-24):
```js
hasBoost: Boolean,
weeklyMatchCount: Number,
```

Computed visibility (lines 82-83):
```js
const isPro = page.props.subscription?.is_pro;
const showMatchCounter = computed(() => !props.hasBoost && !isPro && isActive.value);
```

Shows when **all three** are true:
- Group does NOT have an active boost
- Current user is NOT a Pro subscriber
- Session status is `active` (not completed)

Template (lines 369-377):
```html
<div v-if="showMatchCounter" class="flex justify-center">
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
        :class="weeklyMatchCount >= 4 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'"
    >
        {{ weeklyMatchCount }}/5 free matches this week
    </span>
</div>
```

- Positioned between the completed session summary block and the match history list
- Gray pill by default, amber at >= 4
- No link, no button — informational only

---

## 5. Play Button Color (Emerald Green)

**Purpose:** Make the Play buttons stand out with brand green instead of the default gray-800 PrimaryButton.

### What was changed

The `PrimaryButton` component itself was **NOT** modified. It remains gray-800 globally. The green override is applied inline with `!important` Tailwind classes on the specific buttons.

### Files and locations

**Dashboard** — `resources/js/Pages/Dashboard.vue` (line 39):
```html
<PrimaryButton class="!bg-emerald-600 hover:!bg-emerald-500 focus:!bg-emerald-500 active:!bg-emerald-700 focus:!ring-emerald-500">
    Play
</PrimaryButton>
```

**Groups/Show** — `resources/js/Pages/Groups/Show.vue` (line 85):
```html
<PrimaryButton class="!bg-emerald-600 hover:!bg-emerald-500 focus:!bg-emerald-500 active:!bg-emerald-700 focus:!ring-emerald-500">
    Play
</PrimaryButton>
```

### Why `!important`

PrimaryButton (`resources/js/Components/PrimaryButton.vue`) applies `bg-gray-800` directly in its template. Tailwind classes passed via the parent's `class` attribute would lose specificity without `!important`.

### If you want to change the color

Change `emerald-600` / `emerald-500` / `emerald-700` in both files. Or, to make all PrimaryButtons green globally, edit the component itself at `resources/js/Components/PrimaryButton.vue` line 3.

---

## 6. End Session Modal Bug Fix

**Problem:** Clicking "End Session" on the confirmation modal would successfully end the session, but the modal stayed open because `showEndModal` was never set back to `false`.

**File:** `resources/js/Pages/Play/Session.vue` (lines 152-156)

**Before:**
```js
function endSessionOnly() {
    endForm.post(route('play.end-session', [props.group.slug, props.session.id]));
}
```

**After:**
```js
function endSessionOnly() {
    endForm.post(route('play.end-session', [props.group.slug, props.session.id]), {
        onSuccess: () => { showEndModal.value = false; },
    });
}
```

The `onSuccess` callback fires after Inertia completes the redirect, closing the modal.

### Related: how the modal works

- `showEndModal` (ref, line 150) — controls visibility
- The `<ConfirmationModal>` component is at the bottom of the template (line 379+)
- `@confirm` triggers `endSessionOnly`, `@cancel` sets `showEndModal = false`

---

## 7. Pricing Page (/pricing)

**Purpose:** Public page comparing Free, Pro, and Group Boost with side-by-side cards.

### Route

**File:** `routes/web.php` (lines 24-26)
```php
Route::get('/pricing', function () {
    return Inertia::render('Pricing');
})->name('pricing');
```

- Public route — no auth middleware
- No controller, no data passed (pricing is hardcoded in the template)
- Route name: `pricing`

### Page structure

**File:** `resources/js/Pages/Pricing.vue`

**Layout:** Standalone (no AuthenticatedLayout). Uses the same dark nav as the Welcome page.

**Structure:**
1. **Nav** (lines 12-43) — dark bg, logo links to `/`, shows Dashboard/Login/Register based on auth state
2. **Header** (lines 46-55) — dark bg, "Simple, transparent pricing" headline
3. **Cards section** (lines 58-213) — 3 cards in a `lg:grid-cols-3` grid, overlapping the header via `-mt-20`
4. **Footer** (lines 217-224) — pushed to bottom via `mt-auto` inside a `min-h-screen flex flex-col` wrapper

### Card details

| Card | Border | Badge | Price | Savings | CTA Button |
|------|--------|-------|-------|---------|------------|
| Free | `border-gray-200` | none | $0/mo | — | Gray outline → Dashboard or Register |
| Pro | `border-emerald-500` (2px) | "Personal" emerald pill | $5/mo billed annually | Save $24/yr vs monthly ($7/mo) | Emerald solid → Billing or Register |
| Group Boost | `border-amber-400` (2px) | "Group Boost" amber pill | $10.83/mo billed annually | Save $50/yr vs monthly ($15/mo) | Amber solid → Billing or Register |

### Pricing math

- **Pro:** $60/yr ÷ 12 = **$5/mo** billed annually. Monthly price is $7/mo. Savings: $84 - $60 = **$24/yr**.
- **Group Boost:** $130/yr ÷ 12 = **$10.83/mo** billed annually. Monthly price is $15/mo. Savings: $180 - $130 = **$50/yr**.

### Pro vs Boost clarification

The footnote below the cards (lines 209-212):
> "Pro and Group Boost are independent — Pro upgrades **your** account across all groups, while a Boost unlocks a **specific group** for every member. Use one, the other, or both."

This is critical — they are NOT tiers. A user can have Pro only, Boost only, both, or neither.

### Key layout decisions

- `min-h-screen flex flex-col` wrapper ensures footer always reaches the bottom
- `flex-1` on the cards section fills remaining space
- `mt-auto` on the footer pushes it down
- Pro and Boost cards use `px-8 pb-10 pt-12` (extra top padding) to prevent the absolute-positioned badge from overlapping the title
- `whitespace-nowrap` on the "Group Boost" badge prevents text wrapping

---

## 8. Dev Artisan Commands

**File:** `routes/console.php`

### `dev:max-matches {group_slug}`

**Purpose:** Fill a group's weekly match count to 5/5 to trigger the limit for testing.

**How it works:**
1. Finds the group by slug
2. Calculates current week start using the group's timezone
3. Counts existing matches this week
4. If count < 5, finds the group's most recent match and copies its `session_id`, `format`, and `logged_by`
5. Creates dummy `GameMatch` rows with `team_1_score: 11`, `team_2_score: 0`, `status: completed`

**Requirements:** The group must have at least one existing match (to copy required fields from).

**Usage:**
```bash
./vendor/bin/sail artisan dev:max-matches {group_slug}
```

### `dev:reset-matches {group_slug}`

**Purpose:** Reset a group's weekly match count to 0/5.

**How it works:**
1. Finds the group by slug
2. Calculates current week start
3. Updates all matches with `played_at >= weekStart` → sets `played_at` to one week earlier
4. This moves them out of the current week window, so `getWeeklyMatchCount` returns 0

**Important:** This does NOT delete matches — it backdates them. They will still appear in stats, session history, etc. They just won't count toward the weekly limit.

**Usage:**
```bash
./vendor/bin/sail artisan dev:reset-matches {group_slug}
```

---

## 9. File Index — Every File Modified

### Backend (PHP)

| File | Changes |
|------|---------|
| `app/Http/Controllers/GroupController.php` | Added `use SubscriptionService`, added `hasBoost` + `weeklyMatchCount` to `show()` Inertia props |
| `app/Http/Controllers/PlayController.php` | Added `hasBoost` + `weeklyMatchCount` to `session()` Inertia props |
| `app/Services/SubscriptionService.php` | Changed limit from 10 to 5 in `canLogMatch()` + error message |
| `routes/console.php` | Added `dev:max-matches` and `dev:reset-matches` commands |
| `routes/web.php` | Added `GET /pricing` route |

### Frontend (Vue)

| File | Changes |
|------|---------|
| `resources/js/Pages/Dashboard.vue` | Added `usePage` import, `isPro` computed, Pro upsell footnote below stats card, emerald Play button |
| `resources/js/Pages/Groups/Show.vue` | Added `usePage` import, `hasBoost`/`weeklyMatchCount` props, `showMatchCounter` computed, match counter below leaderboard, emerald Play button |
| `resources/js/Pages/Play/Session.vue` | Added `hasBoost`/`weeklyMatchCount` props, `showMatchCounter` computed, match counter pill above match history, end session modal `onSuccess` fix |
| `resources/js/Components/UpgradePrompt.vue` | Changed "10 free matches" to "5 free matches" in default message |
| `resources/js/Pages/Billing/Index.vue` | Changed `/10` to `/5` in match counter display |
| `resources/js/Pages/Pricing.vue` | **New file** — public pricing comparison page |

### Documentation / Config

| File | Changes |
|------|---------|
| `PRODUCT_SPEC.md` | All "10" match limit references changed to "5" (lines 35, 57-59, 461, 469, 516-517) |
| `DEV_COMMANDS.md` | **New file** — dev commands reference |

---

## Debugging Guide

### "CTA not showing on Dashboard"
1. Check `$page.props.subscription.is_pro` — if `true`, the CTA is hidden by design
2. Check `personalStats.games` — must be > 0
3. Trace: `HandleInertiaRequests.php:52` → `SubscriptionService::getUserStatus()` → `isProSubscriber()` → `$user->subscribed('pro')`

### "CTA not showing on Groups/Show"
1. Check `hasBoost` prop — if `true`, counter is hidden
2. Check `$page.props.subscription.is_pro` — if `true`, counter is hidden
3. Trace: `GroupController::show()` lines 90-92

### "CTA not showing on Play/Session"
1. Same as Groups/Show checks above, PLUS:
2. Check `session.status` — must be `active` (not `completed`)
3. The pill only shows during active sessions

### "Match count seems wrong"
1. The count is a live query, not cached. Check `matches` table directly:
   ```sql
   SELECT COUNT(*) FROM matches WHERE group_id = ? AND played_at >= ?;
   ```
2. The week starts Monday in the **group's timezone** (not UTC). Check `groups.timezone`.
3. If timezone is null, defaults to UTC.

### "dev:max-matches fails with field error"
The command copies `session_id`, `format`, `status`, `logged_by` from the group's most recent match. If there are no matches in the group, it errors. Play at least one real match first.

### "Pricing page shows 404"
Check that `routes/web.php` has the `/pricing` route (should be around line 24). It's a public route with no middleware.

### "End session modal still stuck"
Check `Play/Session.vue` line 152-155 — the `endSessionOnly` function must have an `onSuccess` callback that sets `showEndModal.value = false`.
