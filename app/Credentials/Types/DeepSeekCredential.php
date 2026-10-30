<?php

namespace App\Credentials\Types;

class DeepSeekCredential extends ApiKeyCredential
{
    public static function key(): string { return 'deepseek'; }
    protected static function displayName(): string { return 'DeepSeek'; }
    protected static function description(): string { return 'کلید API برای دسترسی به مدل‌های DeepSeek-V3 و DeepSeek-R1'; }
    protected static function icon(): string { return 'brand:deepseek'; }
    protected static function placeholder(): ?string { return 'sk-...'; }
    protected static function help(): string { return 'از پلتفرم platform.deepseek.com دریافت کنید.'; }
}
