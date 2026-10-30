<?php

namespace App\Credentials\Types;

class OpenAiCredential extends ApiKeyCredential
{
    public static function key(): string { return 'openai'; }
    protected static function displayName(): string { return 'OpenAI (ChatGPT)'; }
    protected static function description(): string { return 'کلید API برای دسترسی به مدل‌های GPT-4o, o3 و سایر مدل‌های OpenAI'; }
    protected static function icon(): string { return 'brand:chatgpt'; }
    protected static function placeholder(): ?string { return 'sk-...'; }
    protected static function help(): string { return 'از پلتفرم platform.openai.com دریافت کنید.'; }

    protected static function extraSchema(): array
    {
        return [
            'base_url' => [
                'type'        => 'text',
                'label'       => 'Base URL (اختیاری)',
                'required'    => false,
                'default'     => 'https://api.openai.com/v1',
                'placeholder' => 'https://api.openai.com/v1',
            ],
        ];
    }
}
