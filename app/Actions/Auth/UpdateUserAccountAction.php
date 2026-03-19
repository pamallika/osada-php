<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdateUserAccountAction
{
    public function execute(User $user, array $data): User
    {
        $hasPassword = !empty($user->password);
        $isChangingEmail = isset($data['email']) && $data['email'] !== $user->email;
        $isChangingPassword = isset($data['password']);

        if ($hasPassword && ($isChangingEmail || $isChangingPassword)) {
            if (!isset($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The provided password does not match your current password.'],
                ]);
            }
        }

        if ($isChangingPassword) {
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if ($user->isDirty()) {
            $user->save();
        }

        return $user;
    }
}
