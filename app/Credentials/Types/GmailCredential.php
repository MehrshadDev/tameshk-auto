<?php

namespace App\Credentials\Types;

class GmailCredential extends GoogleOAuthCredential
{
    public static function key(): string
    {
        return 'gmail';
    }

    public static function displayName(): string
    {
        return 'Gmail';
    }

    public static function description(): string
    {
        return 'اتصال به حساب گوگل از طریق OAuth2';
    }

    public static function icon(): string
    {
        return 'brand:gmail';
    }
}
