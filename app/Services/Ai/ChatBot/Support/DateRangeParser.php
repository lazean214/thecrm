<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Support;

use App\Services\Ai\ChatBot\DateRange;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class DateRangeParser
{
    /**
     * Detect a predefined date range mentioned in the message.
     */
    public function detect(string $message): ?DateRange
    {
        $text = strtolower(trim($message));

        if ($this->detectWeekNumbers($text, $range)) {
            return $range;
        }

        if (preg_match('/(?:(?:last|past)\s+)?(\d{1,3})\s+days?\b/', $text, $matches)) {
            $days = max(1, (int) $matches[1]);

            return new DateRange(
                "last {$days} days",
                now()->copy()->subDays($days - 1)->startOfDay(),
                now()->copy()->endOfDay(),
            );
        }

        if (preg_match('/\b(?:this|current)\s+fiscal\s+year\b/', $text)) {
            return $this->fiscalYearRange('this fiscal year');
        }

        if (preg_match('/\b(?:last|previous)\s+fiscal\s+year\b/', $text)) {
            return $this->fiscalYearRange('last fiscal year', previous: true);
        }

        if (preg_match('/\b(?:year\s+to\s+date|ytd)\b/', $text)) {
            $fy = FiscalYear::current();

            return new DateRange('year to date', $fy['start'], now()->copy()->endOfDay());
        }

        if (preg_match('/\b(?:this|current)\s+quarter\b/', $text)) {
            return new DateRange('this quarter', now()->copy()->startOfQuarter(), now()->copy()->endOfQuarter());
        }

        if (preg_match('/\b(?:last|previous)\s+quarter\b/', $text)) {
            return new DateRange('last quarter', now()->copy()->subQuarter()->startOfQuarter(), now()->copy()->subQuarter()->endOfQuarter());
        }

        if (preg_match('/\b(?:this|current)\s+month\b/', $text)) {
            return new DateRange('this month', now()->copy()->startOfMonth(), now()->copy()->endOfMonth());
        }

        if (preg_match('/\b(?:last|previous)\s+month\b/', $text)) {
            return new DateRange('last month', now()->copy()->subMonthNoOverflow()->startOfMonth(), now()->copy()->subMonthNoOverflow()->endOfMonth());
        }

        if (preg_match('/\b(?:this|current)\s+week\b/', $text)) {
            return new DateRange('this week', now()->copy()->startOfWeek(CarbonInterface::MONDAY), now()->copy()->endOfWeek(CarbonInterface::SUNDAY));
        }

        if (preg_match('/\b(?:last|previous)\s+week\b/', $text)) {
            return new DateRange('last week', now()->copy()->subWeek()->startOfWeek(CarbonInterface::MONDAY), now()->copy()->subWeek()->endOfWeek(CarbonInterface::SUNDAY));
        }

        if (preg_match('/\b(?:this|current)\s+(?:calendar\s+)?year\b/', $text)) {
            return new DateRange('this year', now()->copy()->startOfYear(), now()->copy()->endOfYear());
        }

        if (preg_match('/\b(?:last|previous)\s+(?:calendar\s+)?year\b/', $text)) {
            return new DateRange('last year', now()->copy()->subYear()->startOfYear(), now()->copy()->subYear()->endOfYear());
        }

        if (preg_match('/\bweekly\b/', $text)) {
            return new DateRange('this week', now()->copy()->startOfWeek(CarbonInterface::MONDAY), now()->copy()->endOfWeek(CarbonInterface::SUNDAY));
        }

        if (preg_match('/\bmonthly\b/', $text)) {
            return new DateRange('this month', now()->copy()->startOfMonth(), now()->copy()->endOfMonth());
        }

        if (preg_match('/\b(?:annual|yearly)\b/', $text)) {
            return $this->fiscalYearRange('this fiscal year');
        }

        if (preg_match('/\btoday\b/', $text)) {
            return new DateRange('today', now()->copy()->startOfDay(), now()->copy()->endOfDay());
        }

        if (preg_match('/(?:from|between)\s+(\d{4}-\d{2}-\d{2})\s+(?:to|and)\s+(\d{4}-\d{2}-\d{2})/', $text, $matches)) {
            $from = Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $matches[2])->endOfDay();

            return new DateRange("{$matches[1]} to {$matches[2]}", $from, $to);
        }

        return null;
    }

    private function detectWeekNumbers(string $text, ?DateRange &$range): bool
    {
        if (! preg_match('/\bweek\s+(\d{1,2})\b(?:\s*(?:to|-|through)\s*(?:week\s+)?(\d{1,2}))?/', $text, $matches)) {
            return false;
        }

        $weekFrom = (int) $matches[1];
        $weekTo = isset($matches[2]) ? (int) $matches[2] : $weekFrom;
        $fy = FiscalYear::current();

        $weekStart = $fy['start']->copy()->startOfWeek(CarbonInterface::MONDAY);
        $from = $weekStart->copy()->addWeeks($weekFrom - 1);
        $to = $weekStart->copy()->addWeeks($weekTo - 1)->endOfWeek(CarbonInterface::SUNDAY);

        $label = $weekFrom === $weekTo ? "week {$weekFrom}" : "weeks {$weekFrom} to {$weekTo}";
        $range = new DateRange($label, $from, $to);

        return true;
    }

    private function fiscalYearRange(string $label, bool $previous = false): DateRange
    {
        $fy = $previous ? FiscalYear::previous() : FiscalYear::current();

        return new DateRange($label, $fy['start'], $fy['end']);
    }
}
