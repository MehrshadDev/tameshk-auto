<?php

namespace App\Credentials\Types;

class OpenRouterCredential extends ApiKeyCredential
{
    public static function key(): string { return 'openrouter'; }
    protected static function displayName(): string { return 'OpenRouter'; }
    protected static function description(): string { return 'دسترسی یکپارچه به بیش از ۲۰۰ مدل هوش مصنوعی (GPT, Claude, Gemini, Llama و ...)'; }
    protected static function icon(): string { return 'brand:openrouter'; }
    protected static function placeholder(): ?string { return 'sk-or-v1-...'; }
    protected static function help(): string { return 'از سایت openrouter.ai دریافت کنید.'; }
}
