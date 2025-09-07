<?php

namespace App\Helpers;

use App\Models\AuthProvider;

enum AuthProviderType: string
{
    case Email = 'email';
    case Google = 'google';
    case Mobile = 'mobile';
}

class CustomFunctions
{
    public static function createProviders(int $userId, AuthProviderType $initial): void
    {
        foreach (AuthProviderType::cases() as $case) {
            AuthProvider::create([
                'user_id'  => $userId,
                'provider' => $case->value,
                'is_active'=> $initial === $case
            ]);
        }
    }
}

