<?php

namespace App\Http\Requests;

use App\Enums\PlayerPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_1_score' => ['required', 'integer', 'min:0', 'max:99'],
            'team_2_score' => ['required', 'integer', 'min:0', 'max:99'],
            'players' => ['required', 'array', 'min:2', 'max:4'],
            'players.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'players.*.team' => ['required', 'integer', 'in:1,2'],
            'players.*.position' => ['required', Rule::enum(PlayerPosition::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $t1 = (int) $this->input('team_1_score');
            $t2 = (int) $this->input('team_2_score');

            if ($t1 === $t2) {
                $validator->errors()->add('team_1_score', 'Scores cannot be tied.');
            }

            $session = $this->route('session');
            $players = $this->input('players', []);
            $expectedPerTeam = $session->format->value === 'doubles' ? 2 : 1;
            $team1Count = collect($players)->where('team', 1)->count();
            $team2Count = collect($players)->where('team', 2)->count();

            if ($team1Count !== $expectedPerTeam || $team2Count !== $expectedPerTeam) {
                $validator->errors()->add('players', "Each team must have exactly {$expectedPerTeam} player(s).");
            }
        });
    }
}
