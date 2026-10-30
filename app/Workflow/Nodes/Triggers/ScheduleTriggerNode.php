<?php

namespace App\Workflow\Nodes\Triggers;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use Carbon\Carbon;
use Cron\CronExpression;
use DateTimeZone;
use Throwable;

class ScheduleTriggerNode implements NodeContract, PublishableTriggerContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'trigger.schedule',
            name: 'Schedule Trigger',
            category: NodeCategory::Trigger,
            description: 'اجرای خودکار و دوره‌ای ورکفلو در بازه‌های زمانی مشخص (دقیقه، ساعت، روز، هفته یا Cron).',
            icon: 'clock',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'interval_unit' => [
                    'type'     => 'select',
                    'label'    => 'بازه تکرار (Trigger Interval)',
                    'options'  => [
                        ['value' => 'minutes', 'label' => 'دقیقه (Minutes)'],
                        ['value' => 'hours', 'label' => 'ساعت (Hours)'],
                        ['value' => 'days', 'label' => 'روز (Days)'],
                        ['value' => 'weeks', 'label' => 'هفته (Weeks)'],
                        ['value' => 'cron', 'label' => 'عبارت Cron سفارشی (Custom Cron)'],
                    ],
                    'default'  => 'hours',
                    'required' => true,
                ],
                'minutes_interval' => [
                    'type'       => 'number',
                    'label'      => 'هر چند دقیقه یک‌بار؟ (1 تا 59)',
                    'default'    => 15,
                    'min'        => 1,
                    'max'        => 59,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['interval_unit' => 'minutes'],
                ],
                'hours_interval' => [
                    'type'       => 'number',
                    'label'      => 'هر چند ساعت یک‌بار؟ (1 تا 23)',
                    'default'    => 1,
                    'min'        => 1,
                    'max'        => 23,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['interval_unit' => 'hours'],
                ],
                'at_minute' => [
                    'type'       => 'number',
                    'label'      => 'در چه دقیقه‌ای از ساعت؟ (0 تا 59)',
                    'default'    => 0,
                    'min'        => 0,
                    'max'        => 59,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['interval_unit' => 'hours'],
                ],
                'days_interval' => [
                    'type'       => 'number',
                    'label'      => 'هر چند روز یک‌بار؟ (1 تا 31)',
                    'default'    => 1,
                    'min'        => 1,
                    'max'        => 31,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['interval_unit' => 'days'],
                ],
                'weekday' => [
                    'type'       => 'select',
                    'label'      => 'روز هفته',
                    'options'    => [
                        ['value' => '6', 'label' => 'شنبه (Saturday)'],
                        ['value' => '0', 'label' => 'یکشنبه (Sunday)'],
                        ['value' => '1', 'label' => 'دوشنبه (Monday)'],
                        ['value' => '2', 'label' => 'سه‌شنبه (Tuesday)'],
                        ['value' => '3', 'label' => 'چهارشنبه (Wednesday)'],
                        ['value' => '4', 'label' => 'پنج‌شنبه (Thursday)'],
                        ['value' => '5', 'label' => 'جمعه (Friday)'],
                    ],
                    'default'    => '6',
                    'required'   => true,
                    'depends_on' => ['interval_unit' => 'weeks'],
                ],
                'at_hour' => [
                    'type'       => 'number',
                    'label'      => 'ساعت اجرا (0 تا 23)',
                    'default'    => 9,
                    'min'        => 0,
                    'max'        => 23,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['interval_unit' => ['days', 'weeks']],
                ],
                'at_time_minute' => [
                    'type'       => 'number',
                    'label'      => 'دقیقه اجرا (0 تا 59)',
                    'default'    => 0,
                    'min'        => 0,
                    'max'        => 59,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['interval_unit' => ['days', 'weeks']],
                ],
                'cron_expression' => [
                    'type'        => 'text',
                    'label'       => 'عبارت Cron (۵ بخشی)',
                    'default'     => '0 9 * * *',
                    'placeholder' => '*/15 * * * *',
                    'help'        => 'فرمت استاندارد ۵ بخشی: دقیقه ساعت روزِ‌ماه ماه روزِ‌هفته (مثلاً */10 * * * * برای هر ۱۰ دقیقه)',
                    'required'    => true,
                    'depends_on'  => ['interval_unit' => 'cron'],
                ],
                'timezone' => [
                    'type'     => 'select',
                    'label'    => 'منطقه زمانی (Timezone)',
                    'options'  => [
                        ['value' => 'Asia/Tehran', 'label' => 'Asia/Tehran (ایران - تهران)'],
                        ['value' => 'UTC', 'label' => 'UTC (جهانی)'],
                        ['value' => 'Asia/Dubai', 'label' => 'Asia/Dubai (دبی)'],
                        ['value' => 'Europe/London', 'label' => 'Europe/London (لندن)'],
                        ['value' => 'Europe/Berlin', 'label' => 'Europe/Berlin (برلین)'],
                        ['value' => 'America/New_York', 'label' => 'America/New_York (نیویورک)'],
                    ],
                    'default'  => 'Asia/Tehran',
                    'advanced' => true,
                ],
            ],
            color: 'emerald',
        );
    }

    public static function cronError(array $config): ?string
    {
        $expr = self::toCronExpression($config);

        if ($expr === '' || ! CronExpression::isValidExpression($expr)) {
            return 'عبارت Cron واردشده معتبر نیست. یک عبارت ۵ بخشی استاندارد (مثل */15 * * * *) وارد کنید.';
        }

        return null;
    }

    public function execute(NodeContext $context): NodeResult
    {
        if ($error = self::cronError($context->config)) {
            throw NodeExecutionException::because($error);
        }

        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['timestamp'], $input['iso'], $input['cron_expression'])) {
            return NodeResult::of($input);
        }

        $payload = self::buildPayload($context->config);
        $context->log("اجرای زمان‌بندی ({$payload['cron_expression']} | {$payload['timezone']})");

        return NodeResult::of($payload);
    }

    private static function bounded(array $config, string $key, int $default, int $min, int $max): int
    {
        return max($min, min($max, (int) ($config[$key] ?? $default)));
    }

    private static function timezoneFor(array $config): DateTimeZone
    {
        try {
            return new DateTimeZone((string) ($config['timezone'] ?? 'Asia/Tehran'));
        } catch (Throwable) {
            return new DateTimeZone('Asia/Tehran');
        }
    }

    public static function toCronExpression(array $config): string
    {
        return match ((string) ($config['interval_unit'] ?? 'hours')) {
            'minutes' => sprintf('*/%d * * * *', self::bounded($config, 'minutes_interval', 15, 1, 59)),
            'hours'   => sprintf(
                '%d */%d * * *',
                self::bounded($config, 'at_minute', 0, 0, 59),
                self::bounded($config, 'hours_interval', 1, 1, 23),
            ),
            'days'    => sprintf(
                '%d %d */%d * *',
                self::bounded($config, 'at_time_minute', 0, 0, 59),
                self::bounded($config, 'at_hour', 9, 0, 23),
                self::bounded($config, 'days_interval', 1, 1, 31),
            ),
            'weeks'   => sprintf(
                '%d %d * * %d',
                self::bounded($config, 'at_time_minute', 0, 0, 59),
                self::bounded($config, 'at_hour', 9, 0, 23),
                self::bounded($config, 'weekday', 6, 0, 6),
            ),
            'cron'    => trim((string) ($config['cron_expression'] ?? '0 9 * * *')),
            default   => '0 * * * *',
        };
    }

    public static function isDue(array $config, ?Carbon $referenceTime = null): bool
    {
        if (self::cronError($config) !== null) {
            return false;
        }

        $tz = self::timezoneFor($config);
        $now = ($referenceTime ? $referenceTime->copy() : Carbon::now())->setTimezone($tz);

        return (new CronExpression(self::toCronExpression($config)))
            ->isDue($now->toDateTimeString(), $tz->getName());
    }

    public static function buildPayload(array $config): array
    {
        $tz = self::timezoneFor($config);
        $now = Carbon::now($tz);

        return [
            'timestamp'       => $now->timestamp,
            'iso'             => $now->toIso8601String(),
            'readable_date'   => $now->format('Y-m-d'),
            'readable_time'   => $now->format('H:i:s'),
            'day_of_week'     => $now->englishDayOfWeek,
            'timezone'        => $tz->getName(),
            'interval_unit'   => (string) ($config['interval_unit'] ?? 'hours'),
            'cron_expression' => self::toCronExpression($config),
        ];
    }
}
