<?php

namespace App\Credentials\Types;

class GroqCredential extends ApiKeyCredential
{
    public static function key(): string { return 'groq'; }
    protected static function displayName(): string { return 'Groq'; }
    protected static function description(): string { return 'کلید API برای پردازش فوق‌سریع مدل‌های Llama, Mixtral و Qwen روی تراشه‌های LPU'; }
    protected static function icon(): string { return 'brand:groq'; }
    protected static function placeholder(): ?string { return 'gsk_...'; }
    protected static function help(): string { return 'از کنسول console.groq.com دریافت کنید.'; }
}
