<?php

namespace App\Workflow\Nodes\Ai;

class DeepSeekChatModelNode extends BaseChatModelNode
{
    protected static function typeName(): string { return 'ai.model.deepseek'; }
    protected static function displayName(): string { return 'DeepSeek Chat Model'; }
    protected static function description(): string { return 'مدل‌های اقتصادی و قدرتمند DeepSeek (V3 و R1) جهت اتصال به ایجنت.'; }
    protected static function credentialType(): string { return 'deepseek'; }
    protected static function credentialLabel(): string { return 'اعتبارنامه DeepSeek'; }
    protected static function defaultModel(): string { return 'deepseek-chat'; }
    protected static function icon(): string { return 'brand:deepseek'; }
    protected static function color(): string { return 'blue'; }
}
