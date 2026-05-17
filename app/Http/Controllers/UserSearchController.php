<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:5', 'email'],
        ]);

        $users = User::where('email', $request->q)
            ->where('id', '!=', $request->user()->id)
            ->select('id', 'name', 'email')
            ->limit(1)
            ->get();

        return response()->json($users);
    }
}
