<?php

namespace App\Workflow\Nodes\Ai;

class OpenAiChatModelNode extends BaseChatModelNode
{
    protected static function typeName(): string { return 'ai.model.openai'; }
    protected static function displayName(): string { return 'OpenAI Chat Model'; }
    protected static function description(): string { return 'مدل‌های هوشمند ChatGPT (مانند GPT-4o و GPT-4.1) جهت اتصال به ایجنت.'; }
    protected static function credentialType(): string { return 'openai'; }
    protected static function credentialLabel(): string { return 'اعتبارنامه OpenAI'; }
    protected static function defaultModel(): string { return 'gpt-4o-mini'; }
    protected static function icon(): string { return 'brand:chatgpt'; }
    protected static function color(): string { return 'fuchsia'; }
}
