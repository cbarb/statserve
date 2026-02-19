<?php

namespace App\Services;

use App\Enums\TournamentEntryStatus;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use App\Notifications\TournamentJoinedNotification;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;

class TournamentService
{
    public function __construct(
        private GeocodingService $geocoding,
        private SubscriptionService $subscription,
        private BracketService $bracket,
    ) {}

    public function start(Tournament $tournament): void
    {
        $this->bracket->startTournament($tournament);
    }

    public function createPublicTournament(User $creator, array $data): Tournament
    {
        $addressParts = array_filter([
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
        ]);

        $coords = $this->geocoding->geocode(implode(', ', $addressParts));

        return Tournament::create([
            'created_by' => $creator->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'format' => $data['format'],
            'bracket_type' => $data['bracket_type'],
            'max_players' => $data['max_players'],
            'min_rating' => $data['min_rating'] ?? null,
            'max_rating' => $data['max_rating'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'latitude' => $coords['latitude'] ?? null,
            'longitude' => $coords['longitude'] ?? null,
            'is_public' => true,
            'status' => TournamentStatus::Registration,
            'registration_opens_at' => $data['registration_opens_at'] ?? null,
            'registration_closes_at' => $data['registration_closes_at'] ?? null,
            'starts_at' => $data['starts_at'],
        ]);
    }

    public function searchNearby(?string $locationQuery, float $radiusMiles = 50, int $perPage = 15): LengthAwarePaginator
    {
        $query = Tournament::publicOpen()->withCount('entries');

        if ($locationQuery) {
            $coords = $this->geocoding->geocode($locationQuery);

            // Text match on city/state, OR within radius if geocoding succeeded
            $query->where(function ($q) use ($locationQuery, $coords, $radiusMiles) {
                $q->where('city', 'like', "%{$locationQuery}%")
                  ->orWhere('state', 'like', "%{$locationQuery}%");

                if ($coords) {
                    $haversine = "(3959 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
                    $q->orWhere(function ($sub) use ($haversine, $coords, $radiusMiles) {
                        $sub->whereNotNull('latitude')
                            ->whereNotNull('longitude')
                            ->whereRaw("{$haversine} <= ?", [
                                $coords['latitude'], $coords['longitude'], $coords['latitude'], $radiusMiles,
                            ]);
                    });
                }
            });

            // Sort by distance when we have coordinates, otherwise by date
            if ($coords) {
                $haversine = "(3959 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
                $query->selectRaw("tournaments.*, {$haversine} as distance", [
                    $coords['latitude'], $coords['longitude'], $coords['latitude'],
                ])
                ->orderByRaw("CASE WHEN latitude IS NULL THEN 1 ELSE 0 END")
                ->orderByRaw("{$haversine}", [
                    $coords['latitude'], $coords['longitude'], $coords['latitude'],
                ]);
            } else {
                $query->orderBy('starts_at');
            }
        } else {
            $query->orderBy('starts_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function join(User $user, Tournament $tournament, ?int $partnerId = null): TournamentEntry
    {
        if ($tournament->status !== TournamentStatus::Registration) {
            throw new DomainException('This tournament is not accepting registrations.');
        }

        if ($tournament->registration_opens_at && now()->lt($tournament->registration_opens_at)) {
            throw new DomainException('Registration has not opened yet.');
        }

        if ($tournament->registration_closes_at && now()->gt($tournament->registration_closes_at)) {
            throw new DomainException('Registration has closed.');
        }

        if ($tournament->isRegistered($user)) {
            throw new DomainException('You are already registered for this tournament.');
        }

        if ($tournament->registeredCount() >= $tournament->max_players) {
            throw new DomainException('This tournament is full.');
        }

        if ($tournament->format->isDoubles()) {
            if (!$partnerId) {
                throw new DomainException('A partner is required for doubles tournaments.');
            }
            if ($partnerId === $user->id) {
                throw new DomainException('You cannot partner with yourself.');
            }
            if ($tournament->entries()->where('user_id', $partnerId)->exists()) {
                throw new DomainException('Your selected partner is already registered in this tournament.');
            }
        } else {
            $partnerId = null;
        }

        $entry = TournamentEntry::create([
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'partner_id' => $partnerId,
            'status' => TournamentEntryStatus::Registered,
        ]);

        $user->notify(new TournamentJoinedNotification($tournament));

        if ($partnerId) {
            User::find($partnerId)->notify(new TournamentJoinedNotification($tournament));
        }

        return $entry;
    }

    public function leave(User $user, Tournament $tournament): void
    {
        $entry = $tournament->entries()->where('user_id', $user->id)->first();

        if (!$entry) {
            throw new DomainException('You are not registered for this tournament.');
        }

        if ($entry->status !== TournamentEntryStatus::Registered) {
            throw new DomainException('You cannot leave the tournament at this stage.');
        }

        $entry->delete();
    }
}
