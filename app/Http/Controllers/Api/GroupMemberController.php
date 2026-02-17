<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class GroupMemberController extends Controller
{
    public function remove(Group $group, User $user): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function promote(Group $group, User $user): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function transfer(Group $group): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
