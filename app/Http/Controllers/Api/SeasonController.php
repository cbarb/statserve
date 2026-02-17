<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Season;
use Illuminate\Http\JsonResponse;

class SeasonController extends Controller
{
    public function current(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function groupStandings(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function pastSeason(Group $group, Season $season): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
