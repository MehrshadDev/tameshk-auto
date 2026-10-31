<?php

namespace App\Workflow\Support;

use App\Workflow\DTOs\NodeContext;
use Illuminate\Support\Facades\Cache;

final class WorkflowDataStore
{
    private const TTL_DAYS = 90;
    private const MAX_KEYS = 200;

    public static function scopeSchema(): array
    {
        return [
            'scope' => [
                'type'     => 'select',
                'label'    => 'محدوده دسترسی (Scope)',
                'options'  => [
                    ['value' => 'workflow', 'label' => 'فقط همین ورکفلو (ایزوله)'],
                    ['value' => 'user', 'label' => 'مشترک بین ورکفلوهای من'],
                ],
                'default'  => 'workflow',
                'required' => true,
            ],
        ];
    }

    public static function resolveScope(NodeContext $context): string
    {
        $userId = (int) $context->run->workflow->user_id;
        $workflowId = (int) $context->run->workflow_id;
        $scope = (string) ($context->config['scope'] ?? 'workflow');

        if ($scope === 'user') {
            return "wf_datastore_u{$userId}_shared";
        }

        return "wf_datastore_u{$userId}_w$workflowId";
    }

    public static function all(string $storeKey): array
    {
        $data = Cache::get($storeKey, []);

        return is_array($data) ? $data : [];
    }

    public static function get(string $storeKey, string $key, mixed $default = null): mixed
    {
        $items = self::all($storeKey);

        return array_key_exists($key, $items) ? $items[$key] : $default;
    }

    public static function set(string $storeKey, string $key, mixed $value): mixed
    {
        $items = self::all($storeKey);
        $items[$key] = $value;

        if (count($items) > self::MAX_KEYS) {
            $items = array_slice($items, -self::MAX_KEYS, null, true);
        }

        Cache::put($storeKey, $items, now()->addDays(self::TTL_DAYS));

        return $value;
    }

    public static function increment(string $storeKey, string $key, int|float $step = 1): int|float
    {
        $current = self::get($storeKey, $key, 0);
        $base = is_numeric($current) ? ($current + 0) : 0;
        $next = $base + $step;

        self::set($storeKey, $key, $next);

        return $next;
    }

    public static function delete(string $storeKey, string $key): bool
    {
        $items = self::all($storeKey);
        $existed = array_key_exists($key, $items);

        unset($items[$key]);

        Cache::put($storeKey, $items, now()->addDays(self::TTL_DAYS));

        return $existed;
    }
}
