<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Support;

use App\Models\BusinessSetting;
use Carbon\CarbonInterface;

final class FiscalYear
{
    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, label: string}
     */
    public static function current(): array
    {
        $startMonth = (int) BusinessSetting::get('fiscal_year_start_month', 4);
        $startDay = (int) BusinessSetting::get('fiscal_year_start_day', 6);
        $endMonth = (int) BusinessSetting::get('fiscal_year_end_month', 4);
        $endDay = (int) BusinessSetting::get('fiscal_year_end_day', 5);

        $now = now();
        $start = $now->copy()->day(1)->month($startMonth)->day($startDay)->startOfDay();
        $end = $now->copy()->addYear()->day(1)->month($endMonth)->day($endDay)->endOfDay();

        if ($now->lt($start)) {
            $start = $start->subYear();
            $end = $end->subYear();
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->format('Y').'/'.$end->format('Y'),
        ];
    }

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, label: string}
     */
    public static function previous(): array
    {
        $current = self::current();

        return [
            'start' => $current['start']->copy()->subYear(),
            'end' => $current['end']->copy()->subYear(),
            'label' => $current['start']->copy()->subYear()->format('Y').'/'.$current['end']->copy()->subYear()->format('Y'),
        ];
    }
}
