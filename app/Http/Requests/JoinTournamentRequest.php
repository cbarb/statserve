<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('join', $this->route('tournament'));
    }

    public function rules(): array
    {
        $tournament = $this->route('tournament');
        $isDoubles = $tournament->format->isDoubles();

        if ($isDoubles) {
            return [
                'partner_id' => [
                    'required',
                    'exists:users,id',
                    Rule::notIn([$this->user()->id]),
                    Rule::unique('tournament_entries', 'user_id')
                        ->where('tournament_id', $tournament->id),
                ],
            ];
        }

        return [
            'partner_id' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'partner_id.required' => 'You must select a partner for doubles tournaments.',
            'partner_id.not_in' => 'You cannot partner with yourself.',
            'partner_id.unique' => 'This partner is already registered in the tournament.',
        ];
    }
}
