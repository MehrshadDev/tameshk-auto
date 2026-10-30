<?php

namespace App\Workflow\Nodes\Triggers;

use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Facades\Cache;

class WebhookTriggerNode implements NodeContract, PublishableTriggerContract
{
    private const LISTEN_SECONDS = 60;
    private const POLL_MS = 500000;

    public static function cacheKey(int $workflowId): string
    {
        return "webhook_test_payload_$workflowId";
    }

    public static function listeningKey(int $workflowId): string
    {
        return "webhook_test_listening_$workflowId";
    }

    public static function normalizePath(mixed $rawPath): string
    {
        return trim((string) ($rawPath ?? ''), '/');
    }

    public static function pathError(mixed $rawPath, int $workflowId): ?string
    {
        $path = self::normalizePath($rawPath);

        if ($path === '') {
            return null;
        }

        if (mb_strlen($path) > 150 || ! preg_match('/^[a-zA-Z0-9\-_\/]+$/', $path) || str_contains($path, '//')) {
            return 'مسیر سفارشی فقط می‌تواند شامل حروف انگلیسی، اعداد، خط تیره (-)، زیرخط (_) و اسلش (/) باشد.';
        }

        if (preg_match('#^[^/]+/(chat|telegram)$#i', $path)) {
            return 'این مسیر در سیستم رزرو شده است. لطفاً مسیر دیگری انتخاب کنید.';
        }

        $takenByToken = Workflow::query()
            ->whereKeyNot($workflowId)
            ->where('webhook_token', $path)
            ->exists();

        if ($takenByToken) {
            return 'این آدرس سفارشی وبهوک قبلاً انتخاب شده است و باید آن را تغییر دهید.';
        }

        $takenByNode = Node::query()
            ->where('type', 'trigger.webhook')
            ->where('workflow_id', '!=', $workflowId)
            ->whereHas('workflow')
            ->get(['config'])
            ->contains(fn (Node $node) => self::normalizePath($node->config['path'] ?? '') === $path);

        if ($takenByNode) {
            return 'این آدرس سفارشی وبهوک قبلاً انتخاب شده است و باید آن را تغییر دهید.';
        }

        return null;
    }

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'trigger.webhook',
            name: 'Webhook',
            category: NodeCategory::Trigger,
            description: 'آغاز ورکفلو با دریافت درخواست HTTP از سرویس‌ها و سایت‌های خارجی.',
            icon: 'webhook',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'http_method' => [
                    'type'     => 'select',
                    'label'    => 'متد درخواست (HTTP Method)',
                    'options'  => [
                        ['value' => 'GET', 'label' => 'GET'],
                        ['value' => 'POST', 'label' => 'POST'],
                        ['value' => 'PUT', 'label' => 'PUT'],
                        ['value' => 'PATCH', 'label' => 'PATCH'],
                        ['value' => 'DELETE', 'label' => 'DELETE'],
                    ],
                    'default'  => 'POST',
                    'required' => true,
                ],
                'path' => [
                    'type'        => 'text',
                    'label'       => 'مسیر سفارشی (Path - اختیاری)',
                    'placeholder' => 'مثلاً home یا orders/create',
                    'help'        => 'در صورت خالی بودن، از شناسه یکتای پیش‌فرض استفاده می‌شود.',
                    'required'    => false,
                ],
                'authentication' => [
                    'type'     => 'select',
                    'label'    => 'احراز هویت (Authentication)',
                    'options'  => [
                        ['value' => 'none', 'label' => 'بدون احراز هویت (None)'],
                        ['value' => 'bearer', 'label' => 'توکن Bearer (Bearer Auth)'],
                        ['value' => 'basic', 'label' => 'نام کاربری و رمز عبور (Basic Auth)'],
                    ],
                    'default'  => 'none',
                    'required' => true,
                ],
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'http_bearer',
                    'label'           => 'اعتبارنامه توکن Bearer',
                    'required'        => true,
                    'depends_on'      => ['authentication' => 'bearer'],
                ],
                'basic_credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'basic_auth',
                    'label'           => 'اعتبارنامه Basic Auth',
                    'required'        => true,
                    'depends_on'      => ['authentication' => 'basic'],
                ],
                'respond' => [
                    'type'     => 'select',
                    'label'    => 'نحوه پاسخ‌دهی (Respond)',
                    'options'  => [
                        ['value' => 'immediately', 'label' => 'بلافاصله پس از دریافت (Immediately)'],
                        ['value' => 'last_node', 'label' => 'پس از پایان آخرین نود (When Last Node Finishes)'],
                    ],
                    'default'  => 'immediately',
                    'required' => true,
                ],
                'response_code' => [
                    'type'     => 'number',
                    'label'    => 'کد وضعیت پاسخ (Response Code)',
                    'default'  => 200,
                    'min'      => 200,
                    'max'      => 599,
                    'integer'  => true,
                    'advanced' => true,
                ],
                'response_body' => [
                    'type'        => 'text',
                    'label'       => 'پیام پاسخ سفارشی (Response Message)',
                    'default'     => 'Workflow was started',
                    'placeholder' => 'Workflow was started',
                    'advanced'    => true,
                    'depends_on'  => ['respond' => 'immediately'],
                ],
            ],
            color: 'rose',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $workflowId = $context->run->workflow_id;

        if ($error = self::pathError($context->node->config['path'] ?? null, $workflowId)) {
            throw NodeExecutionException::because($error);
        }

        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['headers'], $input['method'])) {
            return NodeResult::of($input);
        }

        $payloadKey = self::cacheKey($workflowId);
        $listenKey = self::listeningKey($workflowId);
        $cancelKey = "cancel_workflow_run_{$context->run->id}";

        Cache::forget($payloadKey);
        Cache::put($listenKey, true, now()->addSeconds(self::LISTEN_SECONDS + 5));

        $context->log('در انتظار دریافت درخواست روی آدرس Test URL (حداکثر ۶۰ ثانیه)...');

        $deadline = microtime(true) + self::LISTEN_SECONDS;

        try {
            while (microtime(true) < $deadline) {
                if (Cache::get($cancelKey)) {
                    throw NodeExecutionException::because('انتظار برای دریافت وبهوک متوقف شد.');
                }

                $captured = Cache::pull($payloadKey);

                if (is_array($captured)) {
                    return NodeResult::of($captured);
                }

                usleep(self::POLL_MS);
            }

            throw NodeExecutionException::because(
                'در مدت ۶۰ ثانیه هیچ درخواستی به Test URL ارسال نشد. دوباره روی دکمه تست کلیک کرده و یک درخواست به آدرس تست ارسال کنید.'
            );
        } finally {
            Cache::forget($listenKey);
        }
    }
}
