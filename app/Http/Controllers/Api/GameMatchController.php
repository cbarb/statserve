<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\GameSession;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameMatchController extends Controller
{
    public function store(Request $request, Group $group, GameSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function index(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function show(Group $group, GameMatch $match): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
