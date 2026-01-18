<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UserSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discord_id'   => 'required|string',
            'username'     => 'required|string',
            'global_name'  => 'nullable|string',
            'avatar'       => 'nullable|string',
        ];
    }
}
