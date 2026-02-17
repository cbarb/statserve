# PickleStats — Product Specification

> This document is the single source of truth for the PickleStats application.
> It covers user flows, feature behavior, business rules, and technical decisions.
> Reference this document when building any feature.

---

## 1. Core Concept

PickleStats is a pickleball stat tracking app for groups of friends and
recreational players. Users create or join groups, log matches using an
interactive court interface, and track individual and team statistics over time.

---

## 2. Tech Stack

- **Backend:** Laravel (API-driven), Laravel Sanctum for auth tokens
- **Frontend (MVP):** Inertia.js + Vue 3 (Composition API, web-first, PWA)
- **Frontend (Phase 2):** React Native or Capacitor for iOS/Android
- **Database:** MySQL or PostgreSQL
- **Cache/Queues:** Redis
- **Payments:** Laravel Cashier + Stripe
- **Real-time (future):** Laravel Reverb for live score updates

---

## 3. Subscription Tiers & Access Rules

### Tiers

| Tier         | Price             | Description                                      |
|--------------|-------------------|--------------------------------------------------|
| Free         | $0                | 5 matches/week per group, basic stats            |
| Pro          | $7/mo · $60/yr    | Unlimited personal logging, tournaments, all-time stats |
| Group Boost  | $15/mo · $130/yr  | Unlimited logging for the entire group, all-time stats for all members |

### Match Logging Access Rules

The **logger** is the user whose account initiates the match (taps "Start Match").
It does not matter who physically holds the phone or taps scores during the game.
The gate is based solely on whose account created the match.

```
When a user taps "Start Match":

1. Does this group have an active Group Boost?
   → YES: Allow match. No limits.
   → NO: Continue to step 2.

2. Is the logger (current user) a Pro subscriber?
   → YES: Allow match. Does NOT count against the group's weekly pool.
   → NO: Continue to step 3.

3. Check the group's weekly free match count.
   → count < 5: Allow match. Increment the group's weekly counter.
   → count >= 5: Block. Show upgrade prompt:
     "Your group has used all 5 free matches this week.
      Upgrade to Pro for unlimited personal logging ($7/mo)
      or boost your group for everyone ($15/mo).
      Resets Monday."
```

The weekly counter resets every Monday at 12:00 AM in the group creator's timezone.
A Pro user's matches NEVER count against the group pool — they are exempt.

### Stats Access Rules

| Data                              | Free           | Pro            | Group Boost (all members) |
|-----------------------------------|----------------|----------------|---------------------------|
| Individual stats (W/L, %, rating) | Daily/Weekly/Monthly | + All-Time + date range | + All-Time + date range |
| Team/partnership stats            | Daily/Weekly/Monthly | + All-Time + date range | + All-Time + date range |
| Head-to-head comparisons          | Daily/Weekly/Monthly | + All-Time + date range | + All-Time + date range |
| Trend graphs & charts             | Last 30 days   | Full history   | Full history              |
| Raw totals (total games, rating)  | Always visible | Always visible | Always visible            |

Note: Raw totals like total games played, overall win rate, and current rating
are always visible even on free tier. What's locked is the historical breakdown,
trend graphs, and the ability to filter by custom date ranges or view all-time
detailed stats.

---

## 4. User Flow — Account & Dashboard

### 4.1 Sign Up / Log In

1. User opens the app for the first time.
2. They are presented with a sign up / log in screen.
3. Sign up requires: name, email, password.
4. After sign up, user lands on the **Main Dashboard**.

### 4.2 Main Dashboard

The main dashboard is the user's home screen. It contains:

**Personal Stats Section (top)**
- Stats for the past 30 days by default (daily/weekly/monthly toggles available).
- Shows: total games played, win/loss record, win %, current rating, current streak.
- All-time stats are locked behind Pro or Group Boost (shown as blurred/locked with upgrade prompt).
- Raw totals (total games ever, overall rating) are always visible regardless of tier.

**My Groups Section (below stats)**
- A list of all groups the user belongs to.
- Each group card shows: group name, number of members, the user's record in that group (e.g., "12-8").
- Tapping a group card navigates to that **Group Dashboard**.

**Create Group Button**
- Prominent button (likely floating action button or top of group list).
- Tapping it prompts the user to enter a group name.
- After naming, the user is taken to the new **Group Dashboard** as the group owner.

**Play Button**
- Prominent button to start a match (see Section 6 for full play flow).

---

## 5. User Flow — Groups

### 5.1 Group Dashboard

When a user taps on a group from the main dashboard, they see:

**Group Stats Section**
- Leaderboard showing all group members ranked by rating.
- Individual stats per player: games won, games lost, win %, rating, total points scored.
- Team/partnership stats: every pair that has played together (e.g., AB, AC, AD, BC, BD, CD) with their record as a team (W/L, win %), sorted by most games played together.
- Time range toggles: Daily, Weekly, Monthly (free), All-Time (Pro/Boost only).
- If a group has many members, only show pairs that have actually played together. Do not show pairs with 0 games.

**Members List**
- Shows all current members with their role (Owner, Admin, Member).

**Invite Link**
- A "Generate Invite Link" button that creates a shareable link.
- Link format: `picklestats.app/join/{code}` where code is a random 8-character string.
- The link can be copied and shared via any messaging app.
- No expiration by default (owner can regenerate to invalidate old links).

**Play Button**
- Same play flow as the main dashboard, but pre-selects this group.

### 5.2 Invite Flow

When someone clicks an invite link:

1. If not logged in → redirect to login screen (with invite context preserved).
2. If no account → redirect to sign up screen (with invite context preserved).
3. After auth → automatically join the group and land on that group's dashboard.
4. If already a member of this group → just redirect to the group dashboard (no error).

### 5.3 Group Ownership & Member Management

**Roles:** Owner, Admin, Member.

**Owner capabilities:**
- All admin capabilities.
- Delete the group.
- Promote members to admin.
- Transfer ownership (manual).

**Admin capabilities:**
- Remove members.
- Regenerate invite link.

**Member capabilities:**
- View stats, play matches, leave group.

**Leaving a group:**
- Any member can leave via group settings.
- If the **owner** leaves, ownership automatically transfers to the longest-tenured member (based on `joined_at` timestamp on the `group_user` pivot table).
- If the owner is the **last member**, leaving deletes the group and all associated data.

---

## 6. User Flow — Play Feature

This is the core feature of the app. The full flow:

### 6.1 Starting a Session

1. User taps the **Play** button (from main dashboard or group dashboard).
2. If from main dashboard: prompt user to select one of their groups.
   If from group dashboard: group is pre-selected, skip to step 3.
3. Show the group's member list with checkboxes.
   - "Select All" option at the top.
   - User checks off which players are present/playing today.
   - Minimum: 2 players (for 1v1). For 2v2, minimum 4 players.
4. User selects the format: **1v1 Singles** or **2v2 Doubles**.
5. Proceed to team setup.

### 6.2 Team Setup — Court View

After selecting players and format, show the **pickleball court (top-down view)**.

**Two modes for team assignment:**

**A) Randomize Teams**
- The app randomly assigns players to teams.
- For 2v2 with exactly 4 players: randomly split into two teams of 2.
- For 2v2 with more than 4 players: randomly pick 4 players for the first game, assign to two teams. The remaining players sit out.
- For 1v1: randomly pick 2 players from the selected pool.

**B) Hand Pick Teams**
- The user manually taps court positions to assign specific players to each spot.
- Empty slots show a "+" icon. Tapping a slot shows a picker with available (unassigned) players.
- User drags or taps players into their positions.

The court view shows:
- Team 1 on top half, Team 2 on bottom half.
- Player names/avatars in their assigned positions (left/right for doubles).
- Net line in the middle.
- NVZ (kitchen) zones marked.

### 6.3 Scoring — Post-Game Entry

**IMPORTANT: Scores are entered AFTER the game is played, not in real-time.**

The players play their physical game on the real court. When they're done, they
come back to the app and:

1. The court view shows the current team setup.
2. Two score input fields (one per team) are displayed below the court.
3. The user enters the final score for each team (e.g., Team 1: 11, Team 2: 7).
4. User taps **"Save & Next Game"** to record the match and move to the next one.
   OR taps **"Save & End Session"** to record the match and finish.

Each saved game records:
- Group ID
- All player IDs and their team assignments (team 1 or team 2, position left/right)
- Final score for each team
- Which team won (derived from scores)
- The format (singles or doubles)
- The date and time the game was played
- Who logged the match (the user who initiated the session)
- Session ID (to group multiple consecutive games together)

### 6.4 Next Game Logic

After saving a game, the "next game" behavior depends on the team assignment mode:

**If teams were hand-picked:**
- The next game starts with the SAME team composition from the previous game.
- The user has the option to change teams before starting (swap players, sub someone in/out).
- Players who sat out in the last game can be swapped in.

**If teams were randomized:**
- The next game will have a DIFFERENT team composition.
- The system uses a rotation algorithm with these rules:
  1. **Fair play time:** Every selected player should get roughly the same number of games. If there are more than 4 players (in 2v2), the system rotates who sits out so everyone plays an equal amount.
  2. **No repeat teams:** The same team pairing should not occur twice in a session, as long as there are unused combinations remaining. Once all combinations have been used, the cycle resets.
  3. **Balance opponents:** Try to vary who plays against whom so everyone faces different opponents across the session.
- The user can still manually override the randomized assignment before starting the game.

**Rotation algorithm (2v2 with N players, N > 4):**
- Track games played per player in the current session.
- For each new game, select the 4 players with the fewest games played (ties broken randomly).
- From those 4, generate a team split that hasn't been used in this session.
- If all splits for that group of 4 have been used, try a different group of 4.
- Display the assignment on the court view. User can accept or shuffle again.

### 6.5 1v1 Singles Mode

The 1v1 flow is identical to 2v2 except:
- Only 2 players are on the court per game (one per side).
- No team/partnership stats are generated — only individual stats.
- If more than 2 players are selected, the rotation ensures fair play time and varied matchups.
- Court view shows one player on each side of the net.

### 6.6 Session Tracking

Multiple consecutive games are grouped into a **session**. A session is created
when the user first taps Play and selects their group/players. It ends when they
tap "Save & End Session" after any game.

The session tracks:
- Which group
- Which players were part of the session (the full pool, not just per-game)
- All matches played during the session
- Session start time and end time
- Total games played

Sessions appear in the group's match history and can be expanded to see individual
game results.

---

## 7. Stats & Data Model

### 7.1 Individual Stats (per player, per group)

- Games played (total, as well as in current time range)
- Games won / lost
- Win rate (%)
- Current rating (ELO or TrueSkill)
- Points scored (total across all games)
- Points conceded (total across all games)
- Point differential (avg scored - avg conceded per game)
- Current streak (consecutive wins or losses)
- Longest win streak
- Record by format (singles W/L, doubles W/L)

### 7.2 Team/Partnership Stats (doubles only, per group)

- Pair record: W/L when these two players are on the same team
- Pair win rate (%)
- Games played together
- Average point differential as a team
- Best partner: which teammate gives this player the highest win rate

### 7.3 Head-to-Head Stats (per group)

- Record vs a specific opponent (in both singles and doubles)
- Point differential vs that opponent

### 7.4 Group Leaderboard

- All members ranked by rating
- Sortable by: rating, win %, games played, current streak
- Filterable by time range (daily, weekly, monthly, all-time for Pro/Boost)

---

## 8. Tournament System (Pro Feature — Post-MVP)

Pro users can create tournaments within a group or open to multiple groups.

### Tournament Setup
- Creator sets: name, format (1v1 or 2v2), bracket type (single elimination,
  double elimination, round robin), max participants, optional rating range
  (min/max) for entry eligibility.
- Registration period with open and close dates.

### Tournament Flow
1. Players register (must meet rating requirements if set).
2. Creator closes registration and the app generates the bracket with seeding
   based on current ratings.
3. Each round's matches use the same Play Mode UI — scores feed into both the
   tournament bracket AND regular group/individual stats.
4. Winners advance through the bracket.
5. Tournament results page shows the full bracket, final standings, and a
   tournament MVP (most dominant performance).

### Key Design Decision
Tournament matches are stored in the regular `matches` table with a
`tournament_round_id` foreign key. This means every tournament game also counts
as a regular match for stat tracking purposes. One system, one source of truth.

---

## 9. Gamification System

The gamification system is designed to drive daily engagement, reward consistent
play, create social dynamics within groups, and give free users a path to earn
bonus match logs through engagement rather than just paying.

### 9.1 XP & Leveling System

Every user has a global XP total and a level. XP is earned across all groups.

**XP Sources:**

| Action                        | XP Earned |
|-------------------------------|-----------|
| Play a match                  | +20 XP    |
| Win a match                   | +10 XP    |
| Win by shutout (11-0)         | +25 XP    |
| Complete a daily challenge     | +30 XP    |
| Complete a weekly challenge    | +75 XP    |
| Earn a new badge              | +50 XP    |
| Play 3+ days in a week        | +40 XP (weekly bonus) |
| First match of the day        | +15 XP (daily bonus)  |
| Play in a tournament match    | +30 XP    |
| Win a tournament              | +200 XP   |

**Level Thresholds:**

| Level | Name              | XP Required | Cumulative XP |
|-------|-------------------|-------------|---------------|
| 1     | Beginner          | 0           | 0             |
| 2     | Novice            | 100         | 100           |
| 3     | Rally Ready       | 200         | 300           |
| 4     | Court Regular     | 350         | 650           |
| 5     | Kitchen King      | 500         | 1,150         |
| 6     | Dink Master       | 700         | 1,850         |
| 7     | Net Ninja         | 1,000       | 2,850         |
| 8     | Court Commander   | 1,400       | 4,250         |
| 9     | Grand Dinkster    | 2,000       | 6,250         |
| 10    | Pickle Legend      | 3,000       | 9,250         |

Levels above 10 continue with increasing XP requirements (+500 per level).
There is no level cap.

**Level Display:**
- Level and XP progress bar shown on user profile and in group leaderboards.
- Level badge appears next to player name in all contexts (match setup, stats, etc.).
- Level-up triggers an in-app celebration animation.

**Level-Up Rewards:**
- Each level-up grants a cosmetic reward (profile border, court theme, avatar flair).
- Cosmetic rewards are permanent once earned.
- Cosmetics are visual only — no gameplay advantage.

### 9.2 Badge / Achievement System

Badges are specific achievements that players unlock by meeting defined criteria.
Badges are grouped into categories and have tiers (Bronze → Silver → Gold → Platinum).

**Badge Categories & Examples:**

**Win Milestones**
| Badge            | Bronze    | Silver     | Gold       | Platinum    |
|------------------|-----------|------------|------------|-------------|
| Victor           | 10 wins   | 50 wins    | 150 wins   | 500 wins    |
| Streak Master    | 3 in a row| 5 in a row | 8 in a row | 12 in a row |
| Shutout King     | 1 shutout | 5 shutouts | 15 shutouts| 50 shutouts |
| Comeback Kid     | 1 comeback from 5+ down | 5 | 15 | 50          |

**Play Volume**
| Badge            | Bronze      | Silver       | Gold         | Platinum      |
|------------------|-------------|--------------|--------------|---------------|
| Court Warrior    | 25 matches  | 100 matches  | 300 matches  | 1,000 matches |
| Session Beast    | 5 matches in one session | 8 | 12 | 15+           |
| Consistency      | Play 3 days in a week | 4 weeks straight | 8 weeks | 16 weeks |
| Early Bird       | Play before 9 AM | 10 times | 25 times | 50 times      |

**Social / Group**
| Badge            | Criteria                                              |
|------------------|-------------------------------------------------------|
| Team Player      | Play with every member of a group as a partner        |
| Rival            | Play 20+ matches against the same person              |
| Group Hopper     | Be a member of 3 / 5 / 8 / 12 groups                 |
| The Closer       | Win the last match of a session 5 / 15 / 30 / 50 times |

**Partnership (Doubles)**
| Badge            | Criteria                                              |
|------------------|-------------------------------------------------------|
| Dynamic Duo      | Win 10 / 25 / 50 / 100 matches with the same partner |
| Chemistry        | Achieve 70%+ win rate with a partner (min 10 games)   |
| Versatile        | Win with 5 / 10 / 15 / 20 different partners         |

**Secret Badges (hidden until unlocked)**
| Badge            | Criteria                                              |
|------------------|-------------------------------------------------------|
| Pickle Rick      | Win 3 matches in a row after losing 3 in a row       |
| The Sandwich     | Win, lose, win, lose, win in consecutive games        |
| Zero to Hero     | Go from last place to first in group leaderboard      |
| Marathon         | Play 10+ matches in a single session                  |

**Badge Display:**
- All earned badges shown on profile page in a grid.
- Player can "pin" up to 3 badges that show next to their name.
- New badge earned → in-app notification + celebration animation.
- Badge progress is visible (e.g., "Victor Silver — 38/50 wins").

### 9.3 Free Play Log Rewards (Weekly Challenges)

Free-tier users can earn **bonus match logs** by completing weekly challenges.
Bonus logs are added to the user's personal bank and can be spent in any group
to log a match beyond the group's 5/week free limit.

**How it works:**

1. Every Monday, each user receives 3 weekly challenges (randomly selected from
   a pool, scaled to their level and activity).
2. Completing a challenge rewards 1-3 bonus logs depending on difficulty.
3. Bonus logs are stored on the user's account (not tied to a group).
4. When a free user tries to log a match in a group that has hit its 5/week
   limit, the app checks if they have bonus logs available. If yes, one bonus
   log is consumed and the match is allowed.
5. Bonus logs do NOT expire. They accumulate in the user's bank.
6. Pro users do not need bonus logs (they have unlimited logging) but they still
   earn them passively — this creates a small incentive to maintain Pro even if
   they downgrade later ("you'll lose your unlimited and burn through your 
   banked logs").

**Challenge Pool Examples:**

| Challenge                          | Difficulty | Reward     |
|------------------------------------|------------|------------|
| Play 3 matches this week           | Easy       | +1 log     |
| Win 2 matches in a single session  | Easy       | +1 log     |
| Play on 2 different days this week | Easy       | +1 log     |
| Win 5 matches this week            | Medium     | +2 logs    |
| Play a 1v1 match                   | Medium     | +1 log     |
| Win with a partner you've never paired with | Medium | +2 logs |
| Win 3 matches in a row             | Hard       | +3 logs    |
| Play matches in 2 different groups | Hard       | +2 logs    |
| Achieve a shutout victory          | Hard       | +3 logs    |

**Challenge Rules:**
- 3 challenges per week, always a mix of difficulties (1 easy, 1 medium, 1 hard).
- Challenges refresh every Monday at the same time as the weekly free match counter.
- Progress toward challenges is shown on the main dashboard.
- A completed challenge shows a "Claim" button. User must tap to claim the reward
  (this creates a satisfying micro-interaction).
- Challenges cannot be rerolled or swapped (keeps it simple for MVP).

**Bonus Log Access Control Update:**

The match logging access flow from Section 3 is updated:

```
When a user taps "Start Match":

1. Does this group have an active Group Boost?
   → YES: Allow match. No limits.
   → NO: Continue to step 2.

2. Is the logger (current user) a Pro subscriber?
   → YES: Allow match. Does NOT count against the group's weekly pool.
   → NO: Continue to step 3.

3. Check the group's weekly free match count.
   → count < 5: Allow match. Increment the group's weekly counter.
   → count >= 5: Continue to step 4.

4. Does the logger have bonus logs in their bank?
   → YES: Allow match. Deduct 1 bonus log from their bank. 
          Does NOT increment the group's weekly counter.
   → NO: Block. Show upgrade prompt.
```

### 9.4 Daily & Weekly Challenges

Beyond the bonus-log weekly challenges, the app has a rolling set of daily
challenges that reward XP (not bonus logs). This gives players a reason to open
the app every day.

**Daily Challenges:**
- 1 new daily challenge each day, resets at midnight (user's timezone).
- Simpler than weekly challenges: "Play a match today" (+15 XP), "Win a match
  today" (+20 XP), "Score 30+ points across all matches today" (+25 XP).
- If not completed, it expires and a new one appears the next day.

**Weekly Challenges (XP-only, separate from bonus-log challenges):**
- 2 additional weekly challenges that award XP only (not bonus logs).
- These are harder: "Win 8 matches this week" (+75 XP), "Play on 4 different
  days" (+100 XP), "Win a match in 2 different groups" (+60 XP).
- Combined with the 3 bonus-log weekly challenges, users have 5 weekly
  challenges total + 1 daily = 6 active challenges at any time.

**Challenge Dashboard:**
- Accessible from the main dashboard, shows all active challenges in a card layout.
- Each card shows: challenge description, progress bar, reward, time remaining.
- Completed challenges have a "Claim" animation.

### 9.5 Seasonal Rankings

The app runs quarterly seasons aligned to calendar quarters:
- **Spring:** March 1 – May 31
- **Summer:** June 1 – August 31
- **Fall:** September 1 – November 30
- **Winter:** December 1 – February 28/29

**How seasons work:**

1. Each group has a **seasonal leaderboard** that runs alongside the all-time
   leaderboard. The seasonal leaderboard only counts matches played during the
   current season.

2. At the start of each season, the seasonal leaderboard resets to zero. All
   players start fresh with a clean W/L record for the season.

3. **Rating does NOT fully reset.** The player's ELO rating gets a soft reset —
   pulled toward the average by ~30%. This means strong players still start
   higher but the gap is compressed, giving everyone a realistic shot at climbing.
   Formula: `new_rating = avg_rating + (old_rating - avg_rating) * 0.7`

4. At the end of a season, the top 3 players in each group earn rewards:
   - **1st place:** Gold season badge + exclusive profile border + 100 XP
   - **2nd place:** Silver season badge + 60 XP
   - **3rd place:** Bronze season badge + 40 XP

5. Season badges are permanent and show which season they were earned in
   (e.g., "Summer 2026 Champion — Court Crushers Group"). They display on the
   player's profile in a trophy case section.

6. A **Season Recap** is generated for every group at season end, showing:
   - Final standings
   - Season MVP (highest rating)
   - Most improved (biggest rating gain)
   - Most active (most matches played)
   - Best partnership (highest pair win rate, min 10 games)
   - Biggest rivalry (most H2H matches)

**Seasonal leaderboard visibility:**
- Free users can see the current season's leaderboard (it's inherently time-limited).
- Past season results are viewable by everyone (they're historical records, not
  ongoing stats). This is not gated behind Pro.

### 9.6 Group Milestones

Groups earn collective achievements that track the group's overall activity and
foster community identity.

**Group Milestone Examples:**

| Milestone                | Tiers                                     |
|--------------------------|-------------------------------------------|
| Games Played (group)     | 50 → 200 → 500 → 1,000 → 5,000          |
| Full House               | Have a session where ALL members play      |
| Everyone vs Everyone     | Every possible 1v1 matchup has been played |
| Session Streak           | Group plays 2 / 4 / 8 / 12 weeks in a row |
| Partnership Bingo        | Every possible doubles pairing has played   |
| Century Club             | A single member hits 100 games in the group |

**How they work:**
- Group milestones display on the Group Dashboard in a dedicated section.
- When a milestone is reached, all group members receive a notification.
- Milestones are purely celebratory — no tangible reward, just bragging rights
  and a badge on the group's profile.
- Progress toward milestones is visible (e.g., "Everyone vs Everyone — 12/15
  matchups played").

### 9.7 Rivalry Detection

The app automatically identifies and highlights rivalries between players.

**How rivalry detection works:**

A rivalry is detected when two players meet ALL of these criteria within a group:
1. They have played at least 10 matches against each other (1v1 or on opposing
   doubles teams).
2. Their head-to-head record is close (within 60/40 or tighter — neither player
   has more than 60% of the wins).
3. They have played against each other in the last 30 days (rivalry is "active").

**Rivalry Display:**
- Active rivalries appear on the Group Dashboard in a "Rivalries" section.
- Each rivalry card shows: the two players, their H2H record, current leader,
  and last match result.
- The rivalry card has a subtle "VS" animation or visual treatment to make it
  feel competitive.
- If a rivalry series is tied, it's highlighted more prominently ("TIED 8-8!").
- A rivalry notification is sent when: the lead changes hands, the series ties
  up, or a milestone is hit (e.g., "You and Jordan have played 25 matches!").

**Rivalry integration with Play Mode:**
- When two rivals are assigned to opposing teams during match setup, a brief
  "Rivalry Match" indicator appears on the court view.

### 9.8 "On Fire" Status

A real-time status indicator for players on a hot streak during a session.

**Rules:**
- A player earns "On Fire" status when they win 3 consecutive matches within
  the same session.
- The status is visible in the session's match setup screen as a fire icon
  next to the player's name.
- The status persists until the player loses a match in that session, at which
  point it is removed.
- "On Fire" resets completely between sessions.

**Display:**
- Fire emoji/icon next to player name on court view during match setup.
- Optional: "On Fire" players are highlighted in the post-session recap.
- If a player maintains "On Fire" for an entire session (min 4 games, no losses),
  they earn the "Untouchable" badge (secret badge).

---

## 10. Additional Features (Post-MVP Roadmap)

### 10.1 Smart Matchmaking / Auto-Balance (Pro)
When there are 6-8 players, the app can suggest balanced team splits based on
ELO ratings. Activated via an "Auto-Balance" button on the team setup screen.
Free users manually assign; Pro gets the smart suggestion.

### 10.2 Shareable Player Cards (Viral + Pro)
Auto-generated images (Spotify Wrapped style) showing a player's weekly or
monthly recap, including season standings and badges earned. Free users get a
basic text version; Pro gets the polished visual card with detailed stats.
Designed for sharing on social media. Season recap cards are auto-generated
for all players at season end.

### 10.3 Court Finder (Future, Ad Revenue)
Map of local pickleball courts with ratings, reviews, and live check-ins.
Monetized through sponsored listings and local facility advertising.

### 10.4 League System (Future, Premium)
Structured seasons with regular season scheduling, standings, playoffs,
and championship tracking. Could be its own premium tier or add-on.

---

## 11. Database Schema Overview

### Core Tables

**users** — id, name, email, password, avatar_url, stripe fields, created_at

**groups** — id, name, slug, created_by (FK → users), invite_code, timezone, created_at

**group_user** (pivot) — id, user_id, group_id, role (enum: owner/admin/member), joined_at

**group_boosts** — id, group_id, user_id (purchaser), stripe_subscription_id, status (enum: active/cancelled/expired), starts_at, ends_at

### Match Tables

**sessions** — id, group_id, started_by (FK → users), format (enum: singles/doubles), team_mode (enum: random/handpick), status (enum: active/completed), player_ids (JSON array of all players in the pool), started_at, finished_at

**matches** — id, group_id, session_id (FK → sessions), format (enum: singles/doubles), status (enum: in_progress/completed/cancelled), team_1_score, team_2_score, winning_team, logged_by (FK → users), played_at, created_at

**match_players** — id, match_id, user_id, team (1 or 2), position (enum: left/right/solo)

### Tournament Tables (Post-MVP)

**tournaments** — id, group_id (nullable), created_by, name, format, bracket_type, max_players, min_rating, max_rating, status (enum: registration/in_progress/completed), registration_opens_at, registration_closes_at, starts_at

**tournament_entries** — id, tournament_id, user_id, partner_id (nullable, for doubles), seed, status (enum: registered/checked_in/eliminated/winner)

**tournament_rounds** — id, tournament_id, round_number, bracket (enum: winners/losers/finals), match_id (FK → matches), entry_1_id, entry_2_id, winner_entry_id, scheduled_at

### Subscription Tables

Managed by Laravel Cashier: **subscriptions**, **subscription_items** (auto-created).

### Gamification Tables

**user_xp_events** — id, user_id, source_type (enum: match/challenge/badge/bonus), source_id (nullable, polymorphic), xp_amount, description, created_at

**user_levels** — id, user_id (unique), current_level, total_xp, created_at, updated_at

**badges** — id, slug (unique), name, description, category (enum: wins/volume/social/partnership/secret), tier (enum: bronze/silver/gold/platinum), criteria_type, criteria_value, xp_reward, icon, is_secret (boolean), created_at

**user_badges** — id, user_id, badge_id, earned_at, is_pinned (boolean, max 3 per user)

**challenges** — id, slug, name, description, type (enum: daily/weekly_xp/weekly_bonus_log), difficulty (enum: easy/medium/hard), criteria_type, criteria_value, xp_reward, bonus_logs_reward (default 0), is_active (boolean)

**user_challenges** — id, user_id, challenge_id, assigned_at, expires_at, progress (integer), target (integer), status (enum: active/completed/claimed/expired), claimed_at (nullable)

**user_bonus_logs** — id, user_id, balance (integer, default 0), updated_at. (Single row per user, updated atomically.)

**user_bonus_log_transactions** — id, user_id, amount (positive = earned, negative = spent), source_type (enum: challenge_reward/match_spent), source_id (nullable), balance_after, created_at

**seasons** — id, name (e.g., "Summer 2026"), starts_at, ends_at, status (enum: upcoming/active/completed)

**season_standings** — id, season_id, group_id, user_id, rating_start, rating_end, wins, losses, final_rank, reward_tier (enum: gold/silver/bronze/none), created_at

**group_milestones** — id, group_id, milestone_slug, tier_reached (integer), reached_at, progress (integer), target (integer)

**rivalries** — id, group_id, user_1_id, user_2_id, user_1_wins, user_2_wins, total_matches, last_match_at, is_active (boolean), detected_at

---

## 12. API Routes

### Auth
- POST `/register` — Create account
- POST `/login` — Get Sanctum token
- POST `/logout` — Revoke token
- GET `/user` — Current user profile
- PUT `/user` — Update profile

### Groups
- GET `/groups` — List user's groups
- POST `/groups` — Create a group (user becomes owner)
- GET `/groups/{group}` — Group detail + members
- PUT `/groups/{group}` — Update group settings
- DELETE `/groups/{group}` — Delete group (owner only)
- POST `/groups/{group}/invite` — Generate/refresh invite link
- POST `/groups/join/{code}` — Join via invite code
- DELETE `/groups/{group}/members/{user}` — Remove member
- POST `/groups/{group}/members/{user}/promote` — Promote to admin
- POST `/groups/{group}/transfer` — Transfer ownership
- DELETE `/groups/{group}/leave` — Leave group (auto-transfer if owner)

### Play / Matches
- POST `/groups/{group}/sessions` — Start a new play session
- PUT `/groups/{group}/sessions/{session}` — Update session (end it)
- POST `/groups/{group}/sessions/{session}/matches` — Save a game within a session
- GET `/groups/{group}/matches` — List matches (paginated, filterable)
- GET `/groups/{group}/matches/{match}` — Match detail

### Stats
- GET `/user/stats` — Current user's personal stats (across all groups)
- GET `/groups/{group}/stats` — Group leaderboard
- GET `/groups/{group}/stats/players/{user}` — Player stats within group
- GET `/groups/{group}/stats/head-to-head?player1={id}&player2={id}` — H2H
- GET `/groups/{group}/stats/partnerships` — Team pair stats

### Subscriptions
- POST `/subscription` — Create Stripe checkout session
- GET `/subscription` — Current subscription status
- PUT `/subscription` — Change plan
- DELETE `/subscription` — Cancel subscription
- POST `/subscription/boost/{group}` — Apply Group Boost to a specific group
- POST `/stripe/webhook` — Stripe webhook handler

### Tournaments (Post-MVP)
- POST `/tournaments` — Create tournament (Pro only)
- GET `/tournaments` — List available tournaments
- GET `/tournaments/{tournament}` — Tournament detail + bracket
- PUT `/tournaments/{tournament}` — Update settings
- POST `/tournaments/{tournament}/register` — Sign up
- DELETE `/tournaments/{tournament}/register` — Withdraw
- POST `/tournaments/{tournament}/start` — Close reg, generate bracket
- GET `/tournaments/{tournament}/bracket` — Current bracket state
- POST `/tournaments/{tournament}/rounds/{round}/result` — Report result

### Gamification
- GET `/user/level` — Current level, XP, progress to next level
- GET `/user/badges` — All earned badges
- PUT `/user/badges/{badge}/pin` — Pin/unpin a badge (max 3 pinned)
- GET `/user/challenges` — Active daily + weekly challenges with progress
- POST `/user/challenges/{challenge}/claim` — Claim completed challenge reward
- GET `/user/bonus-logs` — Current bonus log balance + transaction history
- GET `/groups/{group}/milestones` — Group milestone progress
- GET `/groups/{group}/rivalries` — Active rivalries in the group
- GET `/groups/{group}/season` — Current season standings for group
- GET `/groups/{group}/seasons/{season}` — Past season results
- GET `/seasons/current` — Current season info (name, dates, time remaining)

---

## 13. Key Technical Decisions

1. **Logger = Initiator.** The user whose account taps "Start Match" is the logger. If they are Pro, the match does not count against the group's free weekly pool. This is checked via the user ID on match creation, not by inspecting players.

2. **Ownership auto-transfer.** When a group owner leaves, ownership passes to the longest-tenured member based on `group_user.joined_at`. Last member leaving deletes the group.

3. **Weekly counter resets Monday.** The group's free match counter resets every Monday at 12:00 AM in the group creator's timezone. Implemented as a scheduled Laravel job.

4. **Tournament matches = regular matches.** Tournament games are stored in the same `matches` table with a reference back to `tournament_rounds`. All tournament games count toward regular stats.

5. **Scores are entered post-game.** Players play their physical game, then come back to the app to enter the final score. There is no real-time point-by-point scoring in MVP.

6. **Session groups consecutive games.** A session is a container for multiple matches played in one sitting. It tracks the full player pool and handles rotation logic.

7. **Rotation fairness.** When teams are randomized and there are more players than court spots, the system ensures equal play time and non-repeating team combinations within a session.

8. **Stats time gating.** Free users see daily, weekly, and monthly stats. Pro and Group Boost unlock all-time stats and custom date range filtering. Raw totals (total games, overall rating) are always visible.

9. **Group Boost is tied to a group, not a person.** If the booster leaves the group, the boost stays active until the subscription period ends. The boost applies to ONE group per purchase.

10. **Pair stats only show pairs that have played together.** Do not show all possible combinations — only pairs with at least 1 game together, sorted by most games played.

11. **XP is global, not per-group.** A player has one XP total and one level across the entire app. XP earned in any group contributes to the same level. This encourages multi-group participation.

12. **Bonus logs are per-user, spendable in any group.** Bonus logs earned from weekly challenges are banked on the user's account and can be spent in any group that has hit its weekly free limit. They are consumed one-at-a-time. Balance is tracked atomically to prevent race conditions.

13. **Challenges are assigned, not chosen.** Users receive a fixed set of challenges each day/week. They cannot reroll or swap. This keeps implementation simple and prevents gaming the system.

14. **Seasonal rating soft-reset.** At the start of each season, ratings are compressed toward the mean by 30%. Formula: `new = avg + (old - avg) * 0.7`. This gives returning players a head start without making it impossible for others to climb.

15. **Rivalry detection runs async.** Rivalry calculations are performed by a scheduled job (daily), not on every match save. The `rivalries` table is a materialized view of the H2H data, updated in batch.

16. **"On Fire" is session-scoped and ephemeral.** It is tracked in the session state (not persisted as a separate DB record). The session's match history is sufficient to derive who is "on fire" at any point.

17. **Badge progress is computed, not stored incrementally.** When checking badge eligibility, query the source data (matches, sessions, etc.) directly rather than maintaining running counters. Cache results in Redis for performance. This avoids complex sync logic when matches are edited or deleted.

18. **Group milestones are checked post-match.** After every match is saved, a queued job checks relevant group milestones and updates progress. This keeps the match-save endpoint fast.


claude --resume 0483e184-e286-4b2d-8173-a7ab9bdcd5ca