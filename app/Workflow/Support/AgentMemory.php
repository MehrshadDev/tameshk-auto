<?php

namespace App\Workflow\Support;

use Illuminate\Support\Facades\Cache;

final class AgentMemory
{
    private const TTL_DAYS = 30;

    public static function key(int $userId, int $workflowId, string $session): string
    {
        return "ai_agent_memory_u{$userId}_w{$workflowId}_" . sha1($session);
    }

    public static function get(string $key): array
    {
        return Cache::get($key, []);
    }

    public static function put(string $key, array $history): void
    {
        Cache::put($key, $history, now()->addDays(self::TTL_DAYS));
    }

    public static function forget(string $key): void
    {
        Cache::forget($key);
    }
}
