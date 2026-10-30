<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use Carbon\Carbon;
use DateTimeZone;
use IntlDateFormatter;
use Throwable;

class DateTimeToolNode implements NodeContract, ToolContract
{
    private const UNITS = ['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'];

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
                    'type' => 'text',
                    'label' => 'نام ابزار (انگلیسی)',
                    'default' => 'datetime_helper',
                    'required' => true,
                ],
                'tool_description' => [
                    'type' => 'textarea',
                    'label' => 'توضیحات عملکرد ابزار برای مدل',
                    'default' => 'دریافت زمان و تاریخ دقیق فعلی، تبدیل تقویم و منطقه زمانی، افزودن یا کسر زمان از تاریخ و محاسبه اختلاف بین دو تاریخ.',
                    'required' => true,
                ],
                'default_timezone' => [
                    'type' => 'select',
                    'label' => 'منطقه زمانی پیش‌فرض',
                    'options' => [
                        ['value' => 'Asia/Tehran', 'label' => 'Asia/Tehran (ایران - تهران)'],
                        ['value' => 'UTC', 'label' => 'UTC (جهانی)'],
                        ['value' => 'Asia/Dubai', 'label' => 'Asia/Dubai (دبی)'],
                        ['value' => 'Europe/London', 'label' => 'Europe/London (لندن)'],
                        ['value' => 'America/New_York', 'label' => 'America/New_York (نیویورک)'],
                        ['value' => 'Europe/Berlin', 'label' => 'Europe/Berlin (برلین)'],
                        ['value' => 'Asia/Istanbul', 'label' => 'Asia/Istanbul (استانبول)'],
                    ],
                    'default' => 'Asia/Tehran',
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
            'type' => 'OBJECT',
            'properties' => [
                'action' => [
                    'type' => 'STRING',
                    'description' => 'نوع عملیات مورد نظر: "current_time" (زمان و تاریخ کنونی)، "modify_date" (افزودن یا کم کردن زمان)، "difference" (محاسبه اختلاف دو تاریخ)، "format_date" (قالب‌بندی و تبدیل منطقه زمانی تاریخ)',
                ],
                'date' => [
                    'type' => 'STRING',
                    'description' => 'تاریخ مبدا به صورت متنی یا استاندارد (مانند 2026-10-24 یا 2026-10-24 14:30:00). در صورت خالی بودن در current_time و modify_date زمان فعلی استفاده می‌شود.',
                ],
                'target_date' => [
                    'type' => 'STRING',
                    'description' => 'تاریخ مقصد برای مقایسه یا محاسبه اختلاف در عملیات difference.',
                ],
                'amount' => [
                    'type' => 'INTEGER',
                    'description' => 'مقدار عددی برای افزودن یا کم کردن (مثبت برای آینده، منفی برای گذشته) در عملیات modify_date.',
                ],
                'unit' => [
                    'type' => 'STRING',
                    'description' => 'واحد زمانی در عملیات modify_date یا difference: seconds, minutes, hours, days, weeks, months, years',
                ],
                'timezone' => [
                    'type' => 'STRING',
                    'description' => 'منطقه زمانی مانند Asia/Tehran یا UTC.',
                ],
                'format' => [
                    'type' => 'STRING',
                    'description' => 'فرمت خروجی تاریخ مانند "Y-m-d H:i:s".',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $defaultTz = (string) ($context->config['default_timezone'] ?? 'Asia/Tehran');
        $tz = !empty($arguments['timezone']) ? (string) $arguments['timezone'] : $defaultTz;

        try {
            $timezone = new DateTimeZone($tz);
        } catch (Throwable) {
            $tz = $defaultTz;
            $timezone = new DateTimeZone($tz);
        }

        $action = (string) ($arguments['action'] ?? 'current_time');
        $format = !empty($arguments['format']) ? (string) $arguments['format'] : 'Y-m-d H:i:s';

        try {
            return match ($action) {
                'current_time' => $this->handleCurrentTime($timezone, $format),
                'modify_date' => $this->handleModifyDate($arguments, $timezone, $format),
                'difference' => $this->handleDifference($arguments, $timezone),
                'format_date' => $this->handleFormatDate($arguments, $timezone, $format),
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

    private function handleCurrentTime(DateTimeZone $tz, string $format): array
    {
        $now = Carbon::now($tz);

        return [
            'timestamp'    => $now->timestamp,
            'iso'          => $now->toIso8601String(),
            'formatted'    => $now->format($format),
            ...$this->calendar($now),
            'timezone'     => $tz->getName(),
            'is_leap_year' => $now->isLeapYear(),
        ];
    }

    private function handleModifyDate(array $arguments, DateTimeZone $tz, string $format): array
    {
        $rawDate = trim((string) ($arguments['date'] ?? ''));
        $base = $rawDate !== '' ? Carbon::parse($rawDate, $tz) : Carbon::now($tz);
        $modified = $base->copy()->{'add' . ucfirst($this->unit($arguments))}((int) ($arguments['amount'] ?? 0));

        return [
            'original_date' => $base->format($format),
            'modified_date' => $modified->format($format),
            'iso'           => $modified->toIso8601String(),
            ...$this->calendar($modified),
            'timezone'      => $tz->getName(),
        ];
    }

    private function handleDifference(array $arguments, DateTimeZone $tz): array
    {
        $firstRaw = trim((string) ($arguments['date'] ?? ''));
        $secondRaw = trim((string) ($arguments['target_date'] ?? ''));

        if ($firstRaw === '' || $secondRaw === '') {
            return ['error' => 'برای محاسبه فاصله زمانی، هر دو مقدار date و target_date الزامی هستند.'];
        }

        $first = Carbon::parse($firstRaw, $tz);
        $second = Carbon::parse($secondRaw, $tz);
        $unit = $this->unit($arguments);

        return [
            'from_date'      => $first->toIso8601String(),
            'to_date'        => $second->toIso8601String(),
            'unit'           => $unit,
            'difference'     => $first->{'diffIn' . ucfirst($unit)}($second, false),
            'total_days'     => $first->diffInDays($second, false),
            'total_hours'    => $first->diffInHours($second, false),
            'human_readable' => $first->diffForHumans($second),
        ];
    }

    private function handleFormatDate(array $arguments, DateTimeZone $tz, string $format): array
    {
        $rawDate = trim((string) ($arguments['date'] ?? ''));

        if ($rawDate === '') {
            return ['error' => 'برای تغییر فرمت تاریخ، فیلد date الزامی است.'];
        }

        $date = Carbon::parse($rawDate, $tz);

        return [
            'formatted' => $date->format($format),
            'iso'       => $date->toIso8601String(),
            'timestamp' => $date->timestamp,
            ...$this->calendar($date),
            'timezone'  => $tz->getName(),
        ];
    }

    private function persianDayOfWeek(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            Carbon::SATURDAY => 'شنبه',
            Carbon::SUNDAY => 'یکشنبه',
            Carbon::MONDAY => 'دوشنبه',
            Carbon::TUESDAY => 'سه‌شنبه',
            Carbon::WEDNESDAY => 'چهارشنبه',
            Carbon::THURSDAY => 'پنج‌شنبه',
            Carbon::FRIDAY => 'جمعه',
            default => '',
        };
    }

    private function toPersianDate(Carbon $carbon): string
    {
        if (class_exists(IntlDateFormatter::class)) {
            try {
                $formatter = new IntlDateFormatter(
                    'fa_IR@calendar=persian',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::MEDIUM,
                    $carbon->getTimezone()->getName(),
                    IntlDateFormatter::TRADITIONAL,
                    'yyyy/MM/dd HH:mm:ss'
                );

                $formatted = $formatter->format($carbon->getTimestamp());

                if (is_string($formatted) && $formatted !== '') {
                    return $formatted;
                }
            } catch (Throwable) {
            }
        }

        return $carbon->toIso8601String();
    }

    private function unit(array $arguments): string
    {
        $unit = strtolower(trim((string) ($arguments['unit'] ?? 'days')));

        if (! str_ends_with($unit, 's')) {
            $unit .= 's';
        }

        return in_array($unit, self::UNITS, true) ? $unit : 'days';
    }

    private function calendar(Carbon $date): array
    {
        return [
            'day_of_week'    => $date->englishDayOfWeek,
            'day_of_week_fa' => $this->persianDayOfWeek($date->dayOfWeek),
            'persian_date'   => $this->toPersianDate($date),
        ];
    }
}
