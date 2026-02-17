<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameSessionController extends Controller
{
    public function store(Request $request, Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function update(Request $request, Group $group, GameSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
