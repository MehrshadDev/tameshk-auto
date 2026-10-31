<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\DateTimeHelper;
use Throwable;

class DateTimeNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.datetime',
            name: 'Date & Time',
            category: NodeCategory::Transform,
            description: 'دریافت زمان و تاریخ شمسی و میلادی، افزودن یا کسر زمان و محاسبه فاصله بین دو تاریخ.',
            icon: 'calendar',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'current_time', 'label' => 'دریافت زمان و تاریخ فعلی (Current Date & Time)'],
                        ['value' => 'modify_date', 'label' => 'افزودن یا کسر زمان از تاریخ (Add / Subtract Time)'],
                        ['value' => 'difference', 'label' => 'محاسبه اختلاف دو تاریخ (Date Difference)'],
                        ['value' => 'format_date', 'label' => 'تبدیل فرمت و تقویم تاریخ (Format Date)'],
                    ],
                    'default'  => 'current_time',
                    'required' => true,
                ],
                'date' => [
                    'type'        => 'text',
                    'label'       => 'تاریخ مبدا',
                    'placeholder' => 'مثلاً 2026-10-31 14:30:00 یا {{ input.date }} (خالی = زمان فعلی)',
                    'depends_on'  => ['operation' => ['modify_date', 'difference', 'format_date']],
                ],
                'target_date' => [
                    'type'        => 'text',
                    'label'       => 'تاریخ مقصد',
                    'placeholder' => 'مثلاً 2026-12-01 یا {{ input.expire_at }}',
                    'required'    => true,
                    'depends_on'  => ['operation' => 'difference'],
                ],
                'amount' => [
                    'type'       => 'number',
                    'label'      => 'مقدار تغییر (مثبت برای آینده، منفی برای گذشته)',
                    'default'    => 1,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'modify_date'],
                ],
                'unit' => [
                    'type'       => 'select',
                    'label'      => 'واحد زمانی',
                    'options'    => [
                        ['value' => 'minutes', 'label' => 'دقیقه (Minutes)'],
                        ['value' => 'hours', 'label' => 'ساعت (Hours)'],
                        ['value' => 'days', 'label' => 'روز (Days)'],
                        ['value' => 'weeks', 'label' => 'هفته (Weeks)'],
                        ['value' => 'months', 'label' => 'ماه (Months)'],
                        ['value' => 'years', 'label' => 'سال (Years)'],
                    ],
                    'default'    => 'days',
                    'depends_on' => ['operation' => ['modify_date', 'difference']],
                ],
                'timezone' => [
                    'type'    => 'select',
                    'label'   => 'منطقه زمانی',
                    'options' => [
                        ['value' => 'Asia/Tehran', 'label' => 'Asia/Tehran (ایران - تهران)'],
                        ['value' => 'UTC', 'label' => 'UTC (جهانی)'],
                        ['value' => 'Asia/Dubai', 'label' => 'Asia/Dubai (دبی)'],
                        ['value' => 'Europe/London', 'label' => 'Europe/London (لندن)'],
                        ['value' => 'Europe/Berlin', 'label' => 'Europe/Berlin (برلین)'],
                        ['value' => 'America/New_York', 'label' => 'America/New_York (نیویورک)'],
                    ],
                    'default' => 'Asia/Tehran',
                ],
                'format' => [
                    'type'        => 'text',
                    'label'       => 'فرمت خروجی میلادی',
                    'default'     => 'Y-m-d H:i:s',
                    'placeholder' => 'Y-m-d H:i:s',
                    'advanced'    => true,
                ],
            ],
            color: 'pink',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $operation = (string) ($context->config['operation'] ?? 'current_time');
        $timezone = DateTimeHelper::resolveTimezone((string) ($context->config['timezone'] ?? 'Asia/Tehran'));
        $format = trim((string) ($context->config['format'] ?? '')) ?: 'Y-m-d H:i:s';
        $date = trim((string) ($context->config['date'] ?? ''));

        try {
            $output = match ($operation) {
                'current_time' => DateTimeHelper::currentTime($timezone, $format),
                'modify_date'  => DateTimeHelper::modifyDate(
                    $date,
                    (int) ($context->config['amount'] ?? 1),
                    (string) ($context->config['unit'] ?? 'days'),
                    $timezone,
                    $format,
                ),
                'difference' => (function () use ($context, $date, $timezone) {
                    $target = trim((string) ($context->config['target_date'] ?? ''));
                    if ($target === '') {
                        throw NodeExecutionException::because('تاریخ مقصد (target_date) الزامی است.');
                    }
                    $from = $date !== '' ? $date : 'now';
                    return DateTimeHelper::difference(
                        $from,
                        $target,
                        (string) ($context->config['unit'] ?? 'days'),
                        $timezone,
                    );
                })(),
                'format_date' => DateTimeHelper::formatDate($date, $timezone, $format),
                default       => DateTimeHelper::currentTime($timezone, $format),
            };
        } catch (NodeExecutionException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw NodeExecutionException::because('فرمت تاریخ واردشده معتبر نیست: ' . $e->getMessage());
        }

        return NodeResult::of($output);
    }
}
