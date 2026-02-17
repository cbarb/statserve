<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function show(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function destroy(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function invite(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function join(string $code): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function leave(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
