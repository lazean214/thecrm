<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class TsvByCompanyIntent implements ChatIntent
{
    use ScopesRemittances;

    public const KEY = 'tsv.by_company';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'TSV by company';
    }

    public function description(): string
    {
        return 'Timesheet value grouped by company (agency).';
    }

    public function keywords(): array
    {
        return [
            'by company' => 6,
            'per company' => 6,
            'by agency' => 6,
            'per agency' => 6,
            'agency' => 2,
            'company' => 2,
            'tsv' => 1,
            'timesheet' => 1,
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
        $period = $this->periodLabel($params);
        $currency = $params['currency'] ?? 'gbp';

        $rows = $records->groupBy('company_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'company_id' => $first->company_id,
                    'company' => $first->company?->name ?? '—',
                    'tsv' => (float) $group->sum('amount'),
                    'hours' => (float) $group->sum('hours'),
                ];
            })
            ->sortByDesc('tsv')
            ->values()
            ->take(15)
            ->toArray();

        if ($rows === []) {
            return new BotReply(
                "There is no timesheet activity to report for {$period}.",
                null,
                [],
                ['Show TSV this fiscal year', 'Show my pipeline summary'],
                null,
                [],
                self::KEY,
            );
        }

        $top = $rows[0]['company'];
        $answer = 'Your top agency for '.$period.' is '.$top.' at '.Formatters::money($rows[0]['tsv'], $currency).
            ', out of '.Formatters::plural(count($rows), 'company').'.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['company'].' — '.Formatters::money($row['tsv'], $currency).' ('.Formatters::plural((int) $row['hours'], 'hour').')')
            ->implode(PHP_EOL);

        return new BotReply(
            $answer,
            $detail,
            $rows,
            ['TSV by contact', 'TSV by owner', 'Show TSV summary'],
            null,
            [],
            self::KEY,
        );
    }
}
