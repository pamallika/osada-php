<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EventActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'squad_id' => $this->squad_id ?? null,
            'event_id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'event_id' => 'required|exists:events,id',
            'discord_user_id' => 'required|string',
            'action' => 'required|in:confirm,decline,reserve,join_squad',
            'squad_id' => 'nullable|exists:event_squads,id',
        ];
    }
}
