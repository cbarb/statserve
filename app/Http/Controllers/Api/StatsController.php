<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function userStats(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function groupStats(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function playerStats(Group $group, User $user): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function headToHead(Request $request, Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function partnerships(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
