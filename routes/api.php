<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameMatchController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GroupMemberController;
use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');

// Authenticated routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']);

    // Groups
    Route::apiResource('groups', GroupController::class)->names('api.groups');
    Route::post('/groups/{group}/invite', [GroupController::class, 'invite']);
    Route::post('/groups/join/{code}', [GroupController::class, 'join']);
    Route::delete('/groups/{group}/leave', [GroupController::class, 'leave']);
    Route::delete('/groups/{group}/members/{user}', [GroupMemberController::class, 'remove']);
    Route::post('/groups/{group}/members/{user}/promote', [GroupMemberController::class, 'promote']);
    Route::post('/groups/{group}/transfer', [GroupMemberController::class, 'transfer']);

    // Play / Sessions / Matches
    Route::post('/groups/{group}/sessions', [GameSessionController::class, 'store']);
    Route::put('/groups/{group}/sessions/{session}', [GameSessionController::class, 'update']);
    Route::post('/groups/{group}/sessions/{session}/matches', [GameMatchController::class, 'store']);
    Route::get('/groups/{group}/matches', [GameMatchController::class, 'index']);
    Route::get('/groups/{group}/matches/{match}', [GameMatchController::class, 'show']);

    // Stats
    Route::get('/user/stats', [StatsController::class, 'userStats']);
    Route::get('/groups/{group}/stats', [StatsController::class, 'groupStats']);
    Route::get('/groups/{group}/stats/players/{user}', [StatsController::class, 'playerStats']);
    Route::get('/groups/{group}/stats/head-to-head', [StatsController::class, 'headToHead']);
    Route::get('/groups/{group}/stats/partnerships', [StatsController::class, 'partnerships']);

    // Gamification
    Route::get('/user/level', [GamificationController::class, 'level']);
    Route::get('/user/badges', [GamificationController::class, 'badges']);
    Route::put('/user/badges/{badge}/pin', [GamificationController::class, 'pinBadge']);
    Route::get('/user/challenges', [GamificationController::class, 'challenges']);
    Route::post('/user/challenges/{challenge}/claim', [GamificationController::class, 'claimChallenge']);
    Route::get('/user/bonus-logs', [GamificationController::class, 'bonusLogs']);
    Route::get('/groups/{group}/milestones', [GamificationController::class, 'groupMilestones']);
    Route::get('/groups/{group}/rivalries', [GamificationController::class, 'rivalries']);

    // Seasons
    Route::get('/seasons/current', [SeasonController::class, 'current']);
    Route::get('/groups/{group}/season', [SeasonController::class, 'groupStandings']);
    Route::get('/groups/{group}/seasons/{season}', [SeasonController::class, 'pastSeason']);

    // Subscriptions
    Route::get('/subscription', [SubscriptionController::class, 'show']);
    Route::post('/subscription', [SubscriptionController::class, 'create']);
    Route::put('/subscription', [SubscriptionController::class, 'update']);
    Route::delete('/subscription', [SubscriptionController::class, 'cancel']);
    Route::post('/subscription/boost/{group}', [SubscriptionController::class, 'boost']);
});

// Stripe webhook (no auth, verified by signature)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook']);
