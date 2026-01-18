<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreatePresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discord_id' => 'required|string',
            'name' => 'required|string|max:50',
            'structure' => 'required|array|min:1',
            'structure.*.name' => 'required|string|max:32',
            'structure.*.slots' => 'required|integer|min:0|max:100',
        ];
    }
}
