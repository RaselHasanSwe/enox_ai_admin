<?php

namespace App\Support;

class EnoxSession
{
    public static function clear(): void
    {
        session()->forget([
            'enox_access_token',
            'enox_refresh_token',
            'enox_user',
        ]);
    }

    public static function isAuthenticated(): bool
    {
        return session()->has('enox_access_token');
    }
}
