<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetWinnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setWinner', $this->route('tournament'));
    }

    public function rules(): array
    {
        return [
            'winner_entry_id' => ['required', 'exists:tournament_entries,id'],
        ];
    }
}
