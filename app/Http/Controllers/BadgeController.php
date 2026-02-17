<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Services\BadgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BadgeController extends Controller
{
    public function __construct(
        private BadgeService $badgeService,
    ) {}

    public function index(Request $request): Response
    {
        $badges = $this->badgeService->computeProgress($request->user());

        return Inertia::render('Badges/Index', [
            'badges' => $badges,
        ]);
    }

    public function pin(Request $request, Badge $badge): RedirectResponse
    {
        $result = $this->badgeService->pinBadge($request->user(), $badge);

        if ($result['error']) {
            return Redirect::back()->with('error', $result['error']);
        }

        return Redirect::back()->with('success', $result['pinned'] ? 'Badge pinned' : 'Badge unpinned');
    }
}
