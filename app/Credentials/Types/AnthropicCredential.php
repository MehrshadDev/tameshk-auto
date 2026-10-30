<?php

namespace App\Credentials\Types;

class AnthropicCredential extends ApiKeyCredential
{
    public static function key(): string { return 'anthropic'; }
    protected static function displayName(): string { return 'Anthropic (Claude)'; }
    protected static function description(): string { return 'کلید API برای دسترسی به مدل‌های Claude Sonnet, Opus و Haiku'; }
    protected static function icon(): string { return 'brand:claude'; }
    protected static function placeholder(): ?string { return 'sk-ant-...'; }
    protected static function help(): string { return 'از کنسول console.anthropic.com دریافت کنید.'; }
}
