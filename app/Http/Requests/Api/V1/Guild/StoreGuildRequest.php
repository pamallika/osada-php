<?php

namespace App\Http\Requests\Api\V1\Guild;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:50',
            'logo_url' => 'nullable|url',
        ];
    }
}
