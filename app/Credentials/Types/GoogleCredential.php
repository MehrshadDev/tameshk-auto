<?php

namespace App\Credentials\Types;

class GoogleCredential extends GoogleOAuthCredential
{
    public static function key(): string
    {
        return 'google';
    }

    public static function displayName(): string
    {
        return 'Google';
    }

    public static function description(): string
    {
        return 'اتصال به سرویس‌های گوگل مانند Google Sheets از طریق OAuth2';
    }

    public static function icon(): string
    {
        return 'brand:google';
    }
}
