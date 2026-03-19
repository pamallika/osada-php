<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'global_name' => 'nullable|string|max:255',
            'family_name' => 'required|string|max:255',
            'char_class' => 'nullable|string|max:255',
            'attack' => 'nullable|integer|min:0|max:1000',
            'awakening_attack' => 'nullable|integer|min:0|max:1000',
            'defense' => 'nullable|integer|min:0|max:1000',
        ];
    }
}
