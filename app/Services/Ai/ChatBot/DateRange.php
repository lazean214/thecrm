<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot;

use Carbon\CarbonInterface;

final class DateRange
{
    public function __construct(
        public readonly string $label,
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
    ) {}
}
