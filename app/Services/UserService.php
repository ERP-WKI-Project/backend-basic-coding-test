<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public static function create(array $data): User
    {
        return User::create([
            'employee_number' => $data['employee_number'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
        ]);
    }

    public static function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user->fresh();
    }

    public static function delete(User $user): void
    {
        $user->delete();
    }

    public static function restore(User $user): User
    {
        $user->restore();
        return $user->fresh();
    }
}
