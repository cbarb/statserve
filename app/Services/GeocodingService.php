<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    public function geocode(string $address): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'StatServe') . '/1.0',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->failed()) {
                Log::warning('Geocoding request failed', ['address' => $address, 'status' => $response->status()]);
                return null;
            }

            $results = $response->json();

            if (empty($results)) {
                return null;
            }

            return [
                'latitude' => (float) $results[0]['lat'],
                'longitude' => (float) $results[0]['lon'],
            ];
        } catch (\Exception $e) {
            Log::warning('Geocoding exception', ['address' => $address, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
