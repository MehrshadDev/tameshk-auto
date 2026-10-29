<?php

namespace App\Workflow\Nodes\Ai;

class OpenRouterChatModelNode extends BaseChatModelNode
{
    protected static function typeName(): string { return 'ai.model.openrouter'; }
    protected static function displayName(): string { return 'OpenRouter Chat Model'; }
    protected static function description(): string { return 'دسترسی به تمام مدل‌های روز دنیا (OpenAI, Claude, Gemini, DeepSeek) از طریق OpenRouter.'; }
    protected static function credentialType(): string { return 'openrouter'; }
    protected static function credentialLabel(): string { return 'اعتبارنامه OpenRouter'; }
    protected static function defaultModel(): string { return 'openai/gpt-4o-mini'; }
    protected static function icon(): string { return 'brand:openrouter'; }
    protected static function color(): string { return 'emerald'; }
}
