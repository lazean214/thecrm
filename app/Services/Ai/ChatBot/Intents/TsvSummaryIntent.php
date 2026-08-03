<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class TsvSummaryIntent implements ChatIntent
{
    use ScopesRemittances;

    public const KEY = 'tsv.summary';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'TSV summary';
    }

    public function description(): string
    {
        return 'Total timesheet value, hours and distinct billers for a period.';
    }

    public function keywords(): array
    {
        return [
            'tsv value' => 3,
            'timesheet value' => 3,
            'total value' => 3,
            'tsv' => 2,
            'timesheet' => 2,
            'billing' => 1,
        ];
    }

    public function children(): array
    {
        return [];
    }

    public function requires(): array
    {
        return [];
    }

    public function question(): ?string
    {
        return null;
    }

    public function questionOptions(): array
    {
        return [];
    }

    public function handle(array $params, User $user): BotReply
    {
        $records = $this->remittanceQuery($params, $user)->get();

        $total = (float) $records->sum('amount');
        $hours = (float) $records->sum('hours');
        $billers = $records->pluck('contact_id')->unique()->filter()->count();
        $companies = $records->pluck('company_id')->unique()->filter()->count();
        $period = $this->periodLabel($params);
        $currency = $params['currency'] ?? 'gbp';

        if ($records->isEmpty()) {
            return new BotReply(
                "There is no timesheet activity to report for {$period}.",
                null,
                [],
                ['Show TSV this fiscal year', 'Show top deals', 'Show my pipeline summary'],
                null,
                [],
                self::KEY,
            );
        }

        $answer = 'Your TSV for '.$period.' is '.Formatters::money($total, $currency).
            ' across '.Formatters::plural((int) $hours, 'hour').', '.Formatters::plural($billers, 'active biller').' and '.
            Formatters::plural($companies, 'company').'.';

        $detail = implode(PHP_EOL, [
            'Total TSV:   '.Formatters::money($total, $currency),
            'Total hours: '.Formatters::number($hours),
            'Billers:     '.Formatters::number($billers),
            'Companies:   '.Formatters::number($companies),
        ]);

        return new BotReply(
            $answer,
            $detail,
            [
                ['metric' => 'tsv', 'label' => 'Total TSV', 'value' => $total],
                ['metric' => 'hours', 'label' => 'Total hours', 'value' => $hours],
                ['metric' => 'billers', 'label' => 'Active billers', 'value' => $billers],
                ['metric' => 'companies', 'label' => 'Companies', 'value' => $companies],
            ],
            ['TSV by contact', 'TSV by agency', 'Show timesheet details', 'Show my pipeline summary'],
            null,
            [],
            self::KEY,
        );
    }
}
