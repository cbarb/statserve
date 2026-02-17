<?php

namespace App\Http\Requests;

use App\Enums\MatchFormat;
use App\Enums\TeamMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', Rule::enum(MatchFormat::class)],
            'team_mode' => ['required', Rule::enum(TeamMode::class)],
            'player_ids' => ['required', 'array', 'min:2'],
            'player_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $format = $this->input('format');
            $playerCount = count($this->input('player_ids', []));

            if ($format === MatchFormat::Doubles->value && $playerCount < 4) {
                $validator->errors()->add('player_ids', 'Doubles requires at least 4 players.');
            }

            $group = $this->route('group');
            $memberIds = $group->members()->pluck('users.id')->toArray();
            $invalidIds = array_diff($this->input('player_ids', []), $memberIds);
            if (!empty($invalidIds)) {
                $validator->errors()->add('player_ids', 'All players must be members of this group.');
            }
        });
    }
}
