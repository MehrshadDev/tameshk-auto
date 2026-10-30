<?php

namespace App\Workflow\Nodes\Triggers;

use App\Models\Credential;
use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\WebhookTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\TelegramApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramTriggerNode implements NodeContract, WebhookTriggerContract
{
    private const LISTEN_SECONDS = 60;
    private const POLL_SECONDS = 2;

    public static function definition(): NodeDefinition
    {
        $catalog = self::catalog();

        return new NodeDefinition(
            type: 'trigger.telegram',
            name: 'Telegram Trigger',
            category: NodeCategory::Trigger,
            description: 'آغاز خودکار ورکفلو با دریافت رویدادهای ربات تلگرام؛ نوع رویداد از فیلد «اجرا هنگام» انتخاب می‌شود.',
            icon: 'brand:telegram',
            group: 'telegram',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'telegram',
                    'label'           => 'اعتبارنامه ربات تلگرام',
                    'required'        => true,
                ],
                'updates' => [
                    'type'     => 'multiselect',
                    'label'    => 'اجرا هنگام (Trigger On)',
                    'required' => true,
                    'default'  => ['message'],
                    'help'     => 'یک یا چند نوع رویداد را انتخاب کنید. با هر تغییر، پس از ذخیره webhook دوباره ثبت می‌شود.',
                    'options'  => collect($catalog)
                        ->map(fn (array $item, string $key) => ['value' => $key, 'label' => $item[1]])
                        ->values()
                        ->all(),
                ],
            ],
            presets: collect($catalog)
                ->map(fn (array $item, string $key) => [
                    'key'         => $key,
                    'name'        => $item[0],
                    'description' => $item[2],
                    'config'      => ['updates' => [$key]],
                ])
                ->values()
                ->all(),
            color: 'sky',
        );
    }

    private static function catalog(): array
    {
        return [
            'callback_query' => ['On callback query', 'کلیک روی دکمه (Callback Query)', 'اجرا هنگام کلیک کاربر روی دکمه‌های شیشه‌ای ربات.'],
            'channel_post' => ['On channel post', 'پست کانال (Channel Post)', 'اجرا هنگام انتشار پست جدید در کانالی که ربات در آن عضو است.'],
            'edited_channel_post' => ['On edited channel post', 'ویرایش پست کانال (Edited Channel Post)', 'اجرا هنگام ویرایش یک پست در کانال.'],
            'edited_message' => ['On edited message', 'ویرایش پیام (Edited Message)', 'اجرا هنگام ویرایش یک پیام توسط کاربر.'],
            'inline_query' => ['On inline query', 'کوئری اینلاین (Inline Query)', 'اجرا هنگام جستجوی اینلاین کاربر با نام کاربری ربات.'],
            'message' => ['On message', 'پیام جدید (Message)', 'آغاز خودکار ورکفلو به محض ارسال پیام جدید توسط کاربر به ربات.'],
            'poll' => ['On Poll Change', 'تغییر نظرسنجی (Poll)', 'اجرا هنگام تغییر وضعیت یا آمار یک نظرسنجی ارسال‌شده توسط ربات.'],
            'pre_checkout_query' => ['On pre checkout query', 'پیش از پرداخت (Pre-Checkout Query)', 'اجرا هنگام تأیید نهایی پرداخت پیش از کسر وجه.'],
            'shipping_query' => ['On shipping query', 'آدرس ارسال (Shipping Query)', 'اجرا هنگام درخواست محاسبه هزینه یا گزینه‌های ارسال.'],
        ];
    }

    private function allowedUpdates(array $config): array
    {
        $selected = array_values(array_intersect(
            array_keys(self::catalog()),
            (array) ($config['updates'] ?? []),
        ));

        return $selected !== [] ? $selected : ['message'];
    }

    public function execute(NodeContext $context): NodeResult
    {
        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['update_id'])) {
            return $this->formatResult($input);
        }

        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('برای تست این مرحله، انتخاب اعتبارنامه ربات تلگرام الزامی است.');
        }

        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن ربات تلگرام در اعتبارنامه خالی است.');
        }

        $endpoint = $this->apiUrl($botToken, 'getUpdates');
        $allowed = json_encode($this->allowedUpdates($context->config));

        $context->log('در حال انتظار برای دریافت رویداد تستی از تلگرام (حداکثر ۶۰ ثانیه)...');

        $cancelKey = "cancel_workflow_run_{$context->run->id}";

        try {
            $offset = $this->nextOffset($endpoint);
            $deadline = microtime(true) + self::LISTEN_SECONDS;

            while (microtime(true) < $deadline) {
                if (Cache::get($cancelKey)) {
                    throw NodeExecutionException::because('انتظار برای دریافت رویداد متوقف شد.');
                }

                $response = Http::timeout(self::POLL_SECONDS + 10)->get($endpoint, [
                    'offset'          => $offset,
                    'timeout'         => self::POLL_SECONDS,
                    'limit'           => 1,
                    'allowed_updates' => $allowed,
                ]);

                if ($response->status() === 409) {
                    throw NodeExecutionException::because('این ورکفلو منتشر شده و webhook تلگرام فعال است. برای تست دستی، ابتدا انتشار را لغو کنید.');
                }

                if ($response->failed()) {
                    $desc = data_get($response->json(), 'description', $response->body());
                    throw NodeExecutionException::because("خطای تلگرام: $desc");
                }

                $update = data_get($response->json(), 'result.0');

                if ($update) {
                    $updateId = (int) data_get($update, 'update_id');

                    if ($updateId > 0) {
                        Http::timeout(5)->get($endpoint, ['offset' => $updateId + 1, 'limit' => 1]);
                    }

                    return $this->formatResult($update);
                }
            }

            throw NodeExecutionException::because('در مدت ۶۰ ثانیه رویدادی دریافت نشد (Timeout). مجدداً روی دکمه تست کلیک کرده و بلافاصله رویداد موردنظر را در ربات ایجاد کنید.');
        } catch (NodeExecutionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw NodeExecutionException::because("عدم امکان ارتباط با تلگرام: {$e->getMessage()} (اتصال اینترنت یا پروکسی کانتینر را بررسی کنید)");
        }
    }

    public function registerWebhook(Credential $credential, string $url, string $secret, array $config = []): void
    {
        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن ربات تلگرام در اعتبارنامه خالی است.');
        }

        try {
            $response = Http::timeout(15)->post($this->apiUrl($botToken, 'setWebhook'), [
                'url'             => $url,
                'secret_token'    => $secret,
                'allowed_updates' => $this->allowedUpdates($config),
                'max_connections' => 20,
            ]);
        } catch (Throwable) {
            throw NodeExecutionException::because('عدم امکان ارتباط با تلگرام. اتصال اینترنت یا پروکسی سرور را بررسی کنید.');
        }

        if ($response->failed() || ! $response->json('ok')) {
            $description = (string) data_get($response->json(), 'description', $response->body());

            throw NodeExecutionException::because($this->describeWebhookError($description));
        }
    }

    public function unregisterWebhook(Credential $credential): void
    {
        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            return;
        }

        Http::timeout(15)->post($this->apiUrl($botToken, 'deleteWebhook'), [
            'drop_pending_updates' => false,
        ]);
    }

    private function apiUrl(string $botToken, string $method): string
    {
        return TelegramApi::url($botToken, $method);
    }

    private function describeWebhookError(string $description): string
    {
        return match (true) {
            str_contains($description, 'HTTPS') => 'تلگرام فقط آدرس HTTPS معتبر را برای webhook می‌پذیرد. آدرس عمومی سایت (APP_URL یا TELEGRAM_WEBHOOK_BASE_URL) را بررسی کنید.',
            str_contains($description, 'resolve host') => 'تلگرام نتوانست دامنه webhook را پیدا کند. روی localhost کار نمی‌کند؛ از یک آدرس عمومی (مثل tunnel) استفاده کنید.',
            str_contains($description, 'Unauthorized') => 'توکن ربات تلگرام نامعتبر است. اعتبارنامه را بررسی کنید.',
            default => "ثبت webhook در تلگرام ناموفق بود: $description",
        };
    }

    private function nextOffset(string $endpoint): int
    {
        $response = Http::timeout(10)->get($endpoint, ['offset' => -1]);

        if (! $response->successful()) {
            return 0;
        }

        $lastId = (int) data_get($response->json(), 'result.0.update_id', 0);

        return $lastId > 0 ? $lastId + 1 : 0;
    }

    private function formatResult(array $update): NodeResult
    {
        $type = collect(array_keys($update))->first(fn ($key) => $key !== 'update_id') ?? 'message';
        $payload = (array) data_get($update, $type, []);

        [$chatId, $text, $messageId] = match ($type) {
            'callback_query' => [
                data_get($payload, 'message.chat.id'),
                data_get($payload, 'data', ''),
                data_get($payload, 'message.message_id'),
            ],
            'inline_query' => [data_get($payload, 'from.id'), data_get($payload, 'query', ''), null],
            'poll' => [null, data_get($payload, 'question', ''), null],
            'pre_checkout_query', 'shipping_query' => [
                data_get($payload, 'from.id'),
                data_get($payload, 'invoice_payload', ''),
                null,
            ],
            default => [
                data_get($payload, 'chat.id'),
                data_get($payload, 'text', data_get($payload, 'caption', '')),
                data_get($payload, 'message_id'),
            ],
        };

        $text = (string) ($text ?? '');

        return NodeResult::of([
            'update_id'         => data_get($update, 'update_id'),
            'update_type'       => $type,
            'message_id'        => $messageId,
            'chat_id'           => $chatId,
            'from_id'           => data_get($payload, 'from.id'),
            'text'              => $text,
            'chatInput'         => $text,
            'username'          => data_get($payload, 'from.username', ''),
            'first_name'        => data_get($payload, 'from.first_name', ''),
            'chat_type'         => data_get($payload, 'chat.type', data_get($payload, 'message.chat.type')),
            'callback_query_id' => $type === 'callback_query' ? data_get($payload, 'id') : null,
            'query_id'          => data_get($payload, 'id'),
            'raw'               => $update,
        ]);
    }
}
