<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EventCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_free_registration' => filter_var($this->is_free_registration ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discord_guild_id' => 'required|string',
            'region' => 'required|string',
            'start_at' => 'required|date',
            'total_slots' => 'nullable|integer|min:0', // Сделано необязательным
            'is_free_registration' => 'boolean',
            'preset_id' => 'nullable|exists:guild_presets,id',
            'squads' => 'nullable|array', // Сделано необязательным
            'squads.*.name' => 'required_with:squads|string', // Будет обязательным, только если squads передан
            'squads.*.limit' => 'required_with:squads|integer|min:1', // Будет обязательным, только если squads передан
        ];
    }
}
