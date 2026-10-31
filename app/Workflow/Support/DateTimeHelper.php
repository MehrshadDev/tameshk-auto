<?php

namespace App\Workflow\Support;

use Carbon\Carbon;
use DateTimeZone;
use IntlDateFormatter;
use Throwable;

final class DateTimeHelper
{
    public const UNITS = ['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'];

    public static function resolveTimezone(string $tz, string $fallback = 'Asia/Tehran'): DateTimeZone
    {
        $candidate = trim($tz) !== '' ? trim($tz) : $fallback;

        try {
            return new DateTimeZone($candidate);
        } catch (Throwable) {
            return new DateTimeZone($fallback);
        }
    }

    public static function currentTime(DateTimeZone $tz, string $format = 'Y-m-d H:i:s'): array
    {
        $now = Carbon::now($tz);

        return [
            'timestamp'    => $now->timestamp,
            'iso'          => $now->toIso8601String(),
            'formatted'    => $now->format($format),
            ...self::calendar($now),
            'timezone'     => $tz->getName(),
            'is_leap_year' => $now->isLeapYear(),
        ];
    }

    public static function modifyDate(
        string $rawDate,
        int $amount,
        string $unit,
        DateTimeZone $tz,
        string $format = 'Y-m-d H:i:s',
    ): array {
        $base = trim($rawDate) !== '' ? Carbon::parse(trim($rawDate), $tz) : Carbon::now($tz);
        $normalizedUnit = self::normalizeUnit($unit);
        $modified = $base->copy()->{'add' . ucfirst($normalizedUnit)}($amount);

        return [
            'original_date' => $base->format($format),
            'modified_date' => $modified->format($format),
            'formatted'     => $modified->format($format),
            'iso'           => $modified->toIso8601String(),
            'timestamp'     => $modified->timestamp,
            ...self::calendar($modified),
            'timezone'      => $tz->getName(),
        ];
    }

    public static function difference(
        string $firstRaw,
        string $secondRaw,
        string $unit,
        DateTimeZone $tz,
    ): array {
        $first = Carbon::parse(trim($firstRaw), $tz);
        $second = Carbon::parse(trim($secondRaw), $tz);
        $normalizedUnit = self::normalizeUnit($unit);

        return [
            'from_date'      => $first->toIso8601String(),
            'to_date'        => $second->toIso8601String(),
            'unit'           => $normalizedUnit,
            'difference'     => $first->{'diffIn' . ucfirst($normalizedUnit)}($second, false),
            'total_days'     => $first->diffInDays($second, false),
            'total_hours'    => $first->diffInHours($second, false),
            'human_readable' => $first->diffForHumans($second),
        ];
    }

    public static function formatDate(string $rawDate, DateTimeZone $tz, string $format = 'Y-m-d H:i:s'): array
    {
        $date = trim($rawDate) !== '' ? Carbon::parse(trim($rawDate), $tz) : Carbon::now($tz);

        return [
            'formatted' => $date->format($format),
            'iso'       => $date->toIso8601String(),
            'timestamp' => $date->timestamp,
            ...self::calendar($date),
            'timezone'  => $tz->getName(),
        ];
    }

    public static function normalizeUnit(string $unit): string
    {
        $clean = strtolower(trim($unit));

        if ($clean === '') {
            return 'days';
        }

        if (! str_ends_with($clean, 's')) {
            $clean .= 's';
        }

        return in_array($clean, self::UNITS, true) ? $clean : 'days';
    }

    public static function calendar(Carbon $date): array
    {
        return [
            'day_of_week'    => $date->englishDayOfWeek,
            'day_of_week_fa' => self::persianDayOfWeek($date->dayOfWeek),
            'persian_date'   => self::toPersianDate($date, 'yyyy/MM/dd HH:mm:ss'),
            'persian_ymd'    => self::toPersianDate($date, 'yyyy/MM/dd'),
        ];
    }

    public static function persianDayOfWeek(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            Carbon::SATURDAY  => 'شنبه',
            Carbon::SUNDAY    => 'یکشنبه',
            Carbon::MONDAY    => 'دوشنبه',
            Carbon::TUESDAY   => 'سه‌شنبه',
            Carbon::WEDNESDAY => 'چهارشنبه',
            Carbon::THURSDAY  => 'پنج‌شنبه',
            Carbon::FRIDAY    => 'جمعه',
            default           => '',
        };
    }

    public static function toPersianDate(Carbon $carbon, string $pattern = 'yyyy/MM/dd HH:mm:ss'): string
    {
        if (class_exists(IntlDateFormatter::class)) {
            try {
                $formatter = new IntlDateFormatter(
                    'fa_IR@calendar=persian',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::MEDIUM,
                    $carbon->getTimezone()->getName(),
                    IntlDateFormatter::TRADITIONAL,
                    $pattern,
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
}
