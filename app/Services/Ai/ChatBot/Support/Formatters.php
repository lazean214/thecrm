<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Support;

use App\Enums\DealStage;
use App\Services\Ai\ChatBot\DateRange;
use Illuminate\Support\Str;

final class Formatters
{
    public static function money(float|int|string $value, string $currency = 'gbp'): string
    {
        $symbol = match ($currency) {
            'usd' => '$',
            'eur' => '€',
            default => '£',
        };

        return $symbol.number_format((float) $value, 2);
    }

    public static function number(float|int $value): string
    {
        return number_format((float) $value);
    }

    public static function plural(int $count, string $singular, ?string $plural = null): string
    {
        $plural ??= $singular.'s';

        return $count === 1 ? "1 {$singular}" : "{$count} {$plural}";
    }

    public static function range(DateRange $range): string
    {
        return $range->label.' ('.$range->from->format('d M Y').' to '.$range->to->format('d M Y').')';
    }

    public static function stage(mixed $stage): string
    {
        if ($stage instanceof DealStage) {
            return $stage->value;
        }

        if (is_string($stage) || is_numeric($stage)) {
            return (string) $stage;
        }

        if (is_object($stage) && method_exists($stage, 'getName')) {
            return $stage->getName();
        }

        if (is_object($stage) && method_exists($stage, 'label')) {
            return $stage->label();
        }

        return (string) $stage;
    }

    public static function title(mixed $stage): string
    {
        return Str::title(self::stage($stage));
    }
}
