<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GuildSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discord_id' => 'required|string',
            'name' => 'required|string',
            'public_channel_id' => 'nullable|string',
            'officer_role_ids' => 'array',
        ];
    }
}
