<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterUserAction
{
    /**
     * @param array{email: string, password: string, name?: string} $data
     * @return array{token: string, user: User}
     */
    public function execute(array $data): array
    {
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (isset($data['name'])) {
            $user->profile()->create([
                'global_name' => $data['name'],
                'family_name' => '',
                'char_class' => 'None',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user->load('profile'),
        ];
    }
}
