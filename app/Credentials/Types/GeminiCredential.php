<?php

namespace App\Credentials\Types;

class GeminiCredential extends ApiKeyCredential
{
    public static function key(): string { return 'gemini'; }
    protected static function displayName(): string { return 'Google Gemini'; }
    protected static function description(): string { return 'کلید API برای دسترسی به مدل‌های Gemini'; }
    protected static function icon(): string { return 'brand:gemini'; }
    protected static function help(): string { return 'از Google AI Studio دریافت کنید'; }
}
