<?php

use App\Http\Controllers\BadgeController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupInviteController;
use App\Http\Controllers\GroupMatchController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserSearchController;
use App\Services\BadgeService;
use App\Services\StatsService;
use App\Services\XpService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/pricing', function () {
    return Inertia::render('Pricing');
})->name('pricing');

Route::get('/dashboard', function () {
    $user = auth()->user();
    $groups = $user->groups()->withCount('members')->latest()->limit(5)->get()->map(fn ($group) => [
        'id' => $group->id,
        'name' => $group->name,
        'slug' => $group->slug,
        'members_count' => $group->members_count,
        'role' => $group->pivot->role,
    ]);

    $personalStats = app(StatsService::class)->dashboardStats($user->id);

    $levelData = null;
    if ($user->level) {
        $progress = XpService::xpProgress($user->level);
        $levelData = [
            'current_level' => $user->level->current_level,
            'level_name' => XpService::levelName($user->level->current_level),
            'total_xp' => $user->level->total_xp,
            'xp_current' => $progress['current'],
            'xp_needed' => $progress['needed'],
        ];
    }

    $pinnedBadges = app(BadgeService::class)->getPinnedBadges($user);

    return Inertia::render('Dashboard', [
        'groups' => $groups,
        'personalStats' => $personalStats,
        'levelData' => $levelData,
        'pinnedBadges' => $pinnedBadges,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Public invite preview (guest or auth)
Route::get('/join/{code}', [GroupInviteController::class, 'show'])->name('invite.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User search
    Route::get('/users/search', [UserSearchController::class, 'search'])->middleware('throttle:search')->name('users.search');

    // Group CRUD
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/settings', [GroupController::class, 'edit'])->name('groups.edit');
    Route::patch('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

    // Group members
    Route::delete('/groups/{group}/leave', [GroupMemberController::class, 'leave'])->name('groups.leave');
    Route::delete('/groups/{group}/members/{user}', [GroupMemberController::class, 'destroy'])->name('groups.members.destroy');
    Route::post('/groups/{group}/members/{user}/promote', [GroupMemberController::class, 'promote'])->name('groups.members.promote');
    Route::post('/groups/{group}/transfer', [GroupMemberController::class, 'transfer'])->name('groups.transfer');

    // Group invites
    Route::post('/groups/{group}/invite/regenerate', [GroupInviteController::class, 'regenerate'])->name('groups.invite.regenerate');
    Route::post('/join/{code}', [GroupInviteController::class, 'join'])->middleware('throttle:sensitive')->name('groups.join');

    // Stats
    Route::get('/groups/{group}/matches', [GroupMatchController::class, 'index'])->name('groups.matches');
    Route::patch('/groups/{group}/matches/{match}', [GroupMatchController::class, 'update'])->name('groups.matches.update');
    Route::delete('/groups/{group}/matches/{match}', [GroupMatchController::class, 'destroy'])->name('groups.matches.destroy');

    Route::get('/groups/{group}/stats', [StatsController::class, 'groupStats'])->name('groups.stats');
    Route::get('/groups/{group}/stats/head-to-head', [StatsController::class, 'headToHead'])->name('groups.stats.h2h');
    Route::get('/groups/{group}/stats/partnerships', [StatsController::class, 'partnerships'])->name('groups.stats.partnerships');
    Route::get('/groups/{group}/stats/players/{user}', [StatsController::class, 'playerDetail'])->name('groups.stats.player');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/boost/{group}', [BillingController::class, 'boostCheckout'])->name('billing.boost-checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::delete('/billing/cancel-pro', [BillingController::class, 'cancelPro'])->name('billing.cancel-pro');
    Route::delete('/billing/cancel-boost/{group}', [BillingController::class, 'cancelBoost'])->name('billing.cancel-boost');
    Route::post('/billing/resume-pro', [BillingController::class, 'resumePro'])->name('billing.resume-pro');
    Route::post('/billing/resume-boost/{group}', [BillingController::class, 'resumeBoost'])->name('billing.resume-boost');

    // Tournaments
    Route::get('/tournaments', [TournamentController::class, 'index'])->middleware('throttle:search')->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::post('/tournaments/{tournament}/join', [TournamentController::class, 'join'])->name('tournaments.join');
    Route::delete('/tournaments/{tournament}/leave', [TournamentController::class, 'leave'])->name('tournaments.leave');
    Route::post('/tournaments/{tournament}/start', [TournamentController::class, 'start'])->name('tournaments.start');
    Route::post('/tournaments/{tournament}/rounds/{round}/winner', [TournamentController::class, 'setWinner'])->name('tournaments.set-winner');

    // Badges
    Route::get('/badges', [BadgeController::class, 'index'])->name('badges.index');
    Route::put('/badges/{badge}/pin', [BadgeController::class, 'pin'])->name('badges.pin');

    // Play / Sessions
    Route::get('/play', [PlayController::class, 'index'])->name('play.index');
    Route::get('/groups/{group}/play', [PlayController::class, 'setup'])->name('play.setup');
    Route::post('/groups/{group}/play', [PlayController::class, 'createSession'])->name('play.create-session');
    Route::get('/groups/{group}/play/{session}', [PlayController::class, 'session'])->name('play.session');
    Route::post('/groups/{group}/play/{session}/matches', [PlayController::class, 'storeMatch'])->name('play.store-match');
    Route::post('/groups/{group}/play/{session}/end', [PlayController::class, 'endSession'])->name('play.end-session');
});

require __DIR__.'/auth.php';
