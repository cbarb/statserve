<?php

namespace App\Http\Controllers;

use App\Enums\BracketType;
use App\Enums\MatchFormat;
use App\Http\Requests\JoinTournamentRequest;
use App\Http\Requests\SetWinnerRequest;
use App\Http\Requests\StoreTournamentRequest;
use App\Models\Tournament;
use App\Models\TournamentRound;
use App\Services\BracketService;
use App\Services\TournamentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tournament::class);

        $service = app(TournamentService::class);

        $tournaments = $service->searchNearby(
            $request->query('location'),
            (float) $request->query('radius', 50),
        );

        $tournaments->through(function ($tournament) {
            $tournament->players_count = $tournament->format->isDoubles()
                ? $tournament->entries_count * 2
                : $tournament->entries_count;
            return $tournament;
        });

        return Inertia::render('Tournaments/Index', [
            'tournaments' => $tournaments,
            'filters' => [
                'location' => $request->query('location', ''),
                'radius' => (int) $request->query('radius', 50),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tournament::class);

        return Inertia::render('Tournaments/Create', [
            'formats' => collect(MatchFormat::tournamentFormats())->map(fn ($f) => ['value' => $f->value, 'label' => $f->label()]),
            'bracketTypes' => collect(BracketType::cases())->map(fn ($b) => ['value' => $b->value, 'label' => str_replace('_', ' ', $b->name)]),
        ]);
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $service = app(TournamentService::class);
        $tournament = $service->createPublicTournament($request->user(), $request->validated());

        return Redirect::route('tournaments.show', $tournament);
    }

    public function show(Request $request, Tournament $tournament): Response
    {
        $this->authorize('view', $tournament);

        $entries = $tournament->entries()
            ->with(['user:id,name,avatar_url', 'partner:id,name'])
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'user' => [
                    'id' => $entry->user->id,
                    'name' => $entry->user->name,
                    'avatar_url' => $entry->user->avatar_url,
                ],
                'partner' => $entry->partner ? [
                    'id' => $entry->partner->id,
                    'name' => $entry->partner->name,
                ] : null,
                'status' => $entry->status->value,
            ]);

        // Build bracket rounds data
        $rounds = [];
        $standings = [];

        if ($tournament->status->value !== 'registration') {
            $tournamentRounds = $tournament->rounds()
                ->with(['entry1.user:id,name', 'entry1.partner:id,name', 'entry2.user:id,name', 'entry2.partner:id,name'])
                ->orderBy('bracket')
                ->orderBy('round_number')
                ->orderBy('id')
                ->get();

            foreach ($tournamentRounds as $round) {
                $bracket = $round->bracket->value;
                $roundNum = $round->round_number;

                $rounds[$bracket][$roundNum][] = [
                    'id' => $round->id,
                    'round_number' => $round->round_number,
                    'bracket' => $bracket,
                    'entry_1' => $round->entry1 ? [
                        'id' => $round->entry1->id,
                        'name' => $round->entry1->user->name . ($round->entry1->partner ? ' & ' . $round->entry1->partner->name : ''),
                    ] : null,
                    'entry_2' => $round->entry2 ? [
                        'id' => $round->entry2->id,
                        'name' => $round->entry2->user->name . ($round->entry2->partner ? ' & ' . $round->entry2->partner->name : ''),
                    ] : null,
                    'winner_entry_id' => $round->winner_entry_id,
                ];
            }

            $bracketService = app(BracketService::class);
            $standings = $bracketService->getStandings($tournament);
        }

        return Inertia::render('Tournaments/Show', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'description' => $tournament->description,
                'format' => $tournament->format->value,
                'format_label' => $tournament->format->label(),
                'is_doubles' => $tournament->format->isDoubles(),
                'bracket_type' => $tournament->bracket_type->value,
                'max_players' => $tournament->max_players,
                'players_count' => $tournament->registeredCount(),
                'address' => $tournament->address,
                'city' => $tournament->city,
                'state' => $tournament->state,
                'status' => $tournament->status->value,
                'starts_at' => $tournament->starts_at?->toISOString(),
                'registration_opens_at' => $tournament->registration_opens_at?->toISOString(),
                'registration_closes_at' => $tournament->registration_closes_at?->toISOString(),
                'created_by' => $tournament->created_by,
                'creator_name' => $tournament->creator->name,
                'is_public' => $tournament->is_public,
            ],
            'entries' => $entries,
            'rounds' => $rounds,
            'standings' => $standings,
            'isRegistered' => $tournament->isRegistered($request->user()),
            'isOrganizer' => $request->user()->id === $tournament->created_by,
            'canJoin' => $request->user()->can('join', $tournament),
        ]);
    }

    public function start(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('start', $tournament);

        $service = app(TournamentService::class);

        try {
            $service->start($tournament);
        } catch (DomainException $e) {
            return Redirect::back()->withErrors(['tournament' => $e->getMessage()]);
        }

        return Redirect::route('tournaments.show', $tournament)->with('status', 'Tournament started! Bracket has been generated.');
    }

    public function setWinner(SetWinnerRequest $request, Tournament $tournament, TournamentRound $round): RedirectResponse
    {
        $bracketService = app(BracketService::class);

        try {
            $winner = $tournament->entries()->findOrFail($request->validated('winner_entry_id'));
            $bracketService->setWinner($round, $winner);
        } catch (DomainException $e) {
            return Redirect::back()->withErrors(['tournament' => $e->getMessage()]);
        }

        return Redirect::route('tournaments.show', $tournament);
    }

    public function join(JoinTournamentRequest $request, Tournament $tournament): RedirectResponse
    {
        $service = app(TournamentService::class);

        try {
            $service->join($request->user(), $tournament, $request->validated('partner_id'));
        } catch (DomainException $e) {
            return Redirect::back()->withErrors(['tournament' => $e->getMessage()]);
        }

        return Redirect::route('tournaments.show', $tournament)->with('status', 'You have joined the tournament.');
    }

    public function leave(Request $request, Tournament $tournament): RedirectResponse
    {
        $service = app(TournamentService::class);

        try {
            $service->leave($request->user(), $tournament);
        } catch (DomainException $e) {
            return Redirect::back()->withErrors(['tournament' => $e->getMessage()]);
        }

        return Redirect::route('tournaments.show', $tournament)->with('status', 'You have left the tournament.');
    }
}
