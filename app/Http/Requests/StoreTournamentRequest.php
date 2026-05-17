<?php

namespace App\Http\Requests;

use App\Enums\BracketType;
use App\Enums\MatchFormat;
use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Tournament::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'format' => ['required', Rule::enum(MatchFormat::class)],
            'bracket_type' => ['required', Rule::enum(BracketType::class)],
            'max_players' => ['required', 'integer', 'min:4', 'max:128'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'starts_at' => ['required', 'date', 'after:now'],
            'registration_opens_at' => ['nullable', 'date', 'before:starts_at'],
            'registration_closes_at' => ['nullable', 'date', 'before:starts_at', 'after_or_equal:registration_opens_at'],
            'entry_fee' => ['nullable', 'integer', 'min:100', 'max:100000'],
            'min_rating' => ['nullable', 'integer'],
            'max_rating' => ['nullable', 'integer', 'gte:min_rating'],
        ];
    }
}
