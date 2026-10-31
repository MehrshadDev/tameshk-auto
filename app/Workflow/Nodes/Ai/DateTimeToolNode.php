<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Support\DateTimeHelper;
use Throwable;

class DateTimeToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.datetime',
            name: 'Date & Time Tool',
            category: NodeCategory::Ai,
            description: 'ابزار دریافت زمان و تاریخ جاری، تبدیل مناطق زمانی و انجام محاسبات تقویمی توسط هوش مصنوعی.',
            icon: 'calendar',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'tool_name' => [
                    'type'     => 'text',
                    'label'    => 'نام ابزار (انگلیسی)',
                    'default'  => 'datetime_helper',
                    'required' => true,
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'دریافت زمان و تاریخ دقیق فعلی، تبدیل تقویم شمسی و میلادی، افزودن یا کسر زمان از تاریخ و محاسبه اختلاف بین دو تاریخ.',
                    'required' => true,
                ],
                'default_timezone' => [
                    'type'     => 'select',
                    'label'    => 'منطقه زمانی پیش‌فرض',
                    'options'  => [
                        ['value' => 'Asia/Tehran', 'label' => 'Asia/Tehran (ایران - تهران)'],
                        ['value' => 'UTC', 'label' => 'UTC (جهانی)'],
                        ['value' => 'Asia/Dubai', 'label' => 'Asia/Dubai (دبی)'],
                        ['value' => 'Europe/London', 'label' => 'Europe/London (لندن)'],
                        ['value' => 'America/New_York', 'label' => 'America/New_York (نیویورک)'],
                        ['value' => 'Europe/Berlin', 'label' => 'Europe/Berlin (برلین)'],
                        ['value' => 'Asia/Istanbul', 'label' => 'Asia/Istanbul (استانبول)'],
                    ],
                    'default'  => 'Asia/Tehran',
                    'required' => true,
                ],
            ],
            color: 'pink',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'datetime_helper'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'دریافت و محاسبه تاریخ و زمان'));
    }

    public function toolParameters(array $config): array
    {
        return [
            'type'       => 'OBJECT',
            'properties' => [
                'action' => [
                    'type'        => 'STRING',
                    'description' => 'نوع عملیات مورد نظر: "current_time" (زمان و تاریخ کنونی)، "modify_date" (افزودن یا کم کردن زمان)، "difference" (محاسبه اختلاف دو تاریخ)، "format_date" (قالب‌بندی و تبدیل منطقه زمانی تاریخ)',
                ],
                'date' => [
                    'type'        => 'STRING',
                    'description' => 'تاریخ مبدا به صورت متنی یا استاندارد (مانند 2026-10-24 یا 2026-10-24 14:30:00). در صورت خالی بودن، زمان فعلی استفاده می‌شود.',
                ],
                'target_date' => [
                    'type'        => 'STRING',
                    'description' => 'تاریخ مقصد برای مقایسه یا محاسبه اختلاف در عملیات difference.',
                ],
                'amount' => [
                    'type'        => 'INTEGER',
                    'description' => 'مقدار عددی برای افزودن یا کم کردن (مثبت برای آینده، منفی برای گذشته) در عملیات modify_date.',
                ],
                'unit' => [
                    'type'        => 'STRING',
                    'description' => 'واحد زمانی در عملیات modify_date یا difference: seconds, minutes, hours, days, weeks, months, years',
                ],
                'timezone' => [
                    'type'        => 'STRING',
                    'description' => 'منطقه زمانی مانند Asia/Tehran یا UTC.',
                ],
                'format' => [
                    'type'        => 'STRING',
                    'description' => 'فرمت خروجی تاریخ مانند "Y-m-d H:i:s".',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $defaultTz = (string) ($context->config['default_timezone'] ?? 'Asia/Tehran');
        $timezone = DateTimeHelper::resolveTimezone((string) ($arguments['timezone'] ?? ''), $defaultTz);
        $action = (string) ($arguments['action'] ?? 'current_time');
        $format = ! empty($arguments['format']) ? (string) $arguments['format'] : 'Y-m-d H:i:s';

        try {
            return match ($action) {
                'current_time' => DateTimeHelper::currentTime($timezone, $format),
                'modify_date'  => DateTimeHelper::modifyDate(
                    (string) ($arguments['date'] ?? ''),
                    (int) ($arguments['amount'] ?? 0),
                    (string) ($arguments['unit'] ?? 'days'),
                    $timezone,
                    $format,
                ),
                'difference' => (function () use ($arguments, $timezone) {
                    $first = trim((string) ($arguments['date'] ?? ''));
                    $second = trim((string) ($arguments['target_date'] ?? ''));
                    if ($first === '' || $second === '') {
                        return ['error' => 'برای محاسبه فاصله زمانی، هر دو مقدار date و target_date الزامی هستند.'];
                    }
                    return DateTimeHelper::difference($first, $second, (string) ($arguments['unit'] ?? 'days'), $timezone);
                })(),
                'format_date' => DateTimeHelper::formatDate(
                    (string) ($arguments['date'] ?? ''),
                    $timezone,
                    $format,
                ),
                default => [
                    'error' => "عملیات ناشناخته: {$action}. عملیات‌های معتبر: current_time, modify_date, difference, format_date",
                ],
            };
        } catch (Throwable $e) {
            return [
                'error' => "خطا در پردازش تاریخ و زمان: {$e->getMessage()}",
            ];
        }
    }
}
