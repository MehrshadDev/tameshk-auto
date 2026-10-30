<?php

namespace App\Workflow\Nodes\Ai;

class AnthropicChatModelNode extends BaseChatModelNode
{
    protected static function typeName(): string { return 'ai.model.anthropic'; }
    protected static function displayName(): string { return 'Anthropic Claude Model'; }
    protected static function description(): string { return 'مدل‌های قدرتمند Claude (Sonnet, Opus, Haiku) جهت اتصال به ایجنت.'; }
    protected static function credentialType(): string { return 'anthropic'; }
    protected static function credentialLabel(): string { return 'اعتبارنامه Anthropic'; }
    protected static function icon(): string { return 'brand:claude'; }
    protected static function color(): string { return 'amber'; }
}
