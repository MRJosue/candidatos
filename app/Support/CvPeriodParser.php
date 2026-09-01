<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CvPeriodParser
{
    /**
     * @return array{start_date: ?string, end_date: ?string, is_current: bool}
     */
    public static function dates(?string $period, bool $requiresStartDate): array
    {
        $period = trim((string) $period);
        $tokens = self::dateTokens($period);
        $isCurrent = (bool) preg_match('/\b(?:actual|actualidad|presente|present|current)\b/iu', $period);

        return [
            'start_date' => isset($tokens[0])
                ? self::dateString($tokens[0], false)
                : ($requiresStartDate ? now()->startOfYear()->toDateString() : null),
            'end_date' => (! $isCurrent && isset($tokens[1]))
                ? self::dateString($tokens[1], true)
                : null,
            'is_current' => $isCurrent,
        ];
    }

    public static function textFromDates(mixed $startDate, mixed $endDate, bool $isCurrent): ?string
    {
        if (! $startDate && ! $endDate && ! $isCurrent) {
            return null;
        }

        $start = self::formatDate($startDate);
        $end = $isCurrent ? 'presente' : self::formatDate($endDate);

        return trim(($start ?: '').' - '.($end ?: ''));
    }

    /**
     * @return array<int, array{offset: int, year: int, month: ?int, day: ?int}>
     */
    private static function dateTokens(string $period): array
    {
        if ($period === '') {
            return [];
        }

        $tokens = [];
        $occupied = [];

        self::collectMatches(
            $tokens,
            $occupied,
            '/(?<!\d)(\d{1,2})\s*[\/.-]\s*(\d{1,2})\s*[\/.-]\s*((?:19|20)?\d{2})(?!\d)/u',
            $period,
            function (array $matches): ?array {
                $day = (int) $matches[1][0];
                $month = (int) $matches[2][0];
                $year = self::normalizeYear($matches[3][0]);

                if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year === null) {
                    return null;
                }

                return ['year' => $year, 'month' => $month, 'day' => $day];
            }
        );

        self::collectMatches(
            $tokens,
            $occupied,
            '/(?<![\d\/])(\d{1,2})\s*[\/.-]\s*((?:19|20)?\d{2})(?![\d\/])/u',
            $period,
            function (array $matches): ?array {
                $month = (int) $matches[1][0];
                $year = self::normalizeYear($matches[2][0]);

                if ($month < 1 || $month > 12 || $year === null) {
                    return null;
                }

                return ['year' => $year, 'month' => $month, 'day' => null];
            },
            $occupied
        );

        self::collectMatches(
            $tokens,
            $occupied,
            '/\b(enero|ene|febrero|feb|marzo|mar|abril|abr|mayo|may|junio|jun|julio|jul|agosto|ago|septiembre|setiembre|sep|sept|octubre|oct|noviembre|nov|diciembre|dic|january|jan|february|march|april|june|july|august|aug|september|october|november|december|dec)\.?\s+(?:(\d{1,2})\s+)?((?:19|20)?\d{2})\b/iu',
            $period,
            function (array $matches): ?array {
                $month = self::monthNumber($matches[1][0]);
                $day = isset($matches[2][0]) && $matches[2][0] !== '' ? (int) $matches[2][0] : null;
                $year = self::normalizeYear($matches[3][0]);

                if ($month === null || $year === null || ($day !== null && ($day < 1 || $day > 31))) {
                    return null;
                }

                return ['year' => $year, 'month' => $month, 'day' => $day];
            },
            $occupied
        );

        self::collectMatches(
            $tokens,
            $occupied,
            '/(?<![\d\/])((?:19|20)\d{2})(?![\d\/])/u',
            $period,
            fn (array $matches): array => ['year' => (int) $matches[1][0], 'month' => null, 'day' => null],
            $occupied
        );

        usort($tokens, fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        return array_values($tokens);
    }

    /**
     * @param  array<int, array{offset: int, year: int, month: ?int, day: ?int}>  $tokens
     * @param  array<int, array{0: int, 1: int}>  $occupied
     */
    private static function collectMatches(array &$tokens, array &$occupied, string $pattern, string $period, callable $mapper, array $skipRanges = []): void
    {
        preg_match_all($pattern, $period, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $offset = $match[0][1];
            $length = strlen($match[0][0]);

            if (self::overlaps($offset, $length, $skipRanges)) {
                continue;
            }

            $mapped = $mapper($match);

            if ($mapped === null) {
                continue;
            }

            $tokens[] = [
                'offset' => $offset,
                'year' => $mapped['year'],
                'month' => $mapped['month'],
                'day' => $mapped['day'],
            ];
            $occupied[] = [$offset, $offset + $length];
        }
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $ranges
     */
    private static function overlaps(int $offset, int $length, array $ranges): bool
    {
        $end = $offset + $length;

        foreach ($ranges as [$rangeStart, $rangeEnd]) {
            if ($offset < $rangeEnd && $end > $rangeStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{year: int, month: ?int, day: ?int}  $token
     */
    private static function dateString(array $token, bool $isEndDate): string
    {
        $month = $token['month'] ?? ($isEndDate ? 12 : 1);
        $day = $token['day'] ?? ($isEndDate ? Carbon::create($token['year'], $month, 1)->endOfMonth()->day : 1);

        return Carbon::create($token['year'], $month, $day)->toDateString();
    }

    private static function formatDate(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date->format('m/Y');
        }

        return Carbon::parse($date)->format('m/Y');
    }

    private static function normalizeYear(string $year): ?int
    {
        if (preg_match('/^(?:19|20)\d{2}$/', $year)) {
            return (int) $year;
        }

        if (! preg_match('/^\d{2}$/', $year)) {
            return null;
        }

        $value = (int) $year;

        return $value >= 70 ? 1900 + $value : 2000 + $value;
    }

    private static function monthNumber(string $month): ?int
    {
        $key = mb_strtolower($month);
        $key = str_replace(['á', 'é'], ['a', 'e'], $key);

        return [
            'enero' => 1, 'ene' => 1, 'january' => 1, 'jan' => 1,
            'febrero' => 2, 'feb' => 2, 'february' => 2,
            'marzo' => 3, 'mar' => 3, 'march' => 3,
            'abril' => 4, 'abr' => 4, 'april' => 4, 'apr' => 4,
            'mayo' => 5, 'may' => 5,
            'junio' => 6, 'jun' => 6, 'june' => 6,
            'julio' => 7, 'jul' => 7, 'july' => 7,
            'agosto' => 8, 'ago' => 8, 'august' => 8, 'aug' => 8,
            'septiembre' => 9, 'setiembre' => 9, 'sep' => 9, 'sept' => 9, 'september' => 9,
            'octubre' => 10, 'oct' => 10, 'october' => 10,
            'noviembre' => 11, 'nov' => 11, 'november' => 11,
            'diciembre' => 12, 'dic' => 12, 'december' => 12, 'dec' => 12,
        ][$key] ?? null;
    }
}
