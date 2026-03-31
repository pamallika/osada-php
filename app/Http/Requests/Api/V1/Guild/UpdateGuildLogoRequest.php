<?php

namespace App\Http\Requests\Api\V1\Guild;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuildLogoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'logo' => 'required|image|max:2048|mimes:png,jpg,webp',
        ];
    }
}
