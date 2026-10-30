<?php

namespace App\Workflow\Nodes\Ai;

class GeminiChatModelNode extends BaseChatModelNode
{
    protected static function typeName(): string { return 'ai.model.gemini'; }
    protected static function displayName(): string { return 'Gemini Chat Model'; }
    protected static function description(): string { return 'ارائه‌دهنده مدل هوش مصنوعی Google Gemini جهت اتصال به ایجنت.'; }
    protected static function credentialType(): string { return 'gemini'; }
    protected static function credentialLabel(): string { return 'اعتبارنامه Gemini'; }
    protected static function icon(): string { return 'brand:gemini'; }
    protected static function color(): string { return 'indigo'; }
}
