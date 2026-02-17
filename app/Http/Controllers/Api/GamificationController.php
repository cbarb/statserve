<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Group;
use App\Models\UserChallenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function level(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function badges(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function pinBadge(Request $request, Badge $badge): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function challenges(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function claimChallenge(UserChallenge $challenge): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function bonusLogs(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function groupMilestones(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function rivalries(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
