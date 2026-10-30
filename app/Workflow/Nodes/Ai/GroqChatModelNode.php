<?php

namespace App\Workflow\Nodes\Ai;

class GroqChatModelNode extends BaseChatModelNode
{
    protected static function typeName(): string { return 'ai.model.groq'; }
    protected static function displayName(): string { return 'Groq Chat Model'; }
    protected static function description(): string { return 'اجرای فوق‌سریع مدل‌های متن‌باز (Llama 3.3, Qwen, Mixtral) روی زیرساخت Groq.'; }
    protected static function credentialType(): string { return 'groq'; }
    protected static function credentialLabel(): string { return 'اعتبارنامه Groq'; }
    protected static function icon(): string { return 'brand:groq'; }
    protected static function color(): string { return 'orange'; }
}
