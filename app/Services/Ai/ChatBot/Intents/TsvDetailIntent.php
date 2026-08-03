<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class TsvDetailIntent implements ChatIntent
{
    use ScopesRemittances;

    public const KEY = 'tsv.detail';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Timesheet details';
    }

    public function description(): string
    {
        return 'Detailed timesheet rows with hours, rate, TSV and margin.';
    }

    public function keywords(): array
    {
        return [
            'timesheet details' => 6,
            'tsv details' => 6,
            'timesheet breakdown' => 6,
            'tsv breakdown' => 6,
            'details' => 3,
            'detail' => 3,
            'breakdown' => 3,
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
        $records = $this->remittanceQuery($params, $user)
            ->orderBy('we_date')
            ->orderBy('contact_id')
            ->limit(25)
            ->get();

        $period = $this->periodLabel($params);
        $currency = $params['currency'] ?? 'gbp';

        if ($records->isEmpty()) {
            return new BotReply(
                "There are no timesheet details for {$period}.",
                null,
                [],
                ['Show TSV this fiscal year', 'Show my pipeline summary'],
                null,
                [],
                self::KEY,
            );
        }

        $rows = $records->map(fn ($r) => [
            'week_no' => $r->week_no,
            'worker' => $r->contact ? trim($r->contact->first_name.' '.$r->contact->last_name) : '—',
            'agency' => $r->company?->name ?? '—',
            'shift_date' => $r->shirft_date?->toDateString() ?? '—',
            'we_date' => $r->we_date?->toDateString() ?? '—',
            'hours' => (float) $r->hours,
            'rate' => (float) $r->rate,
            'tsv' => (float) $r->amount,
            'margin' => (float) $r->margin_agreed,
            'status' => $r->status ?? '—',
        ])->toArray();

        $total = array_sum(array_column($rows, 'tsv'));
        $answer = 'Here are '.Formatters::plural(count($rows), 'timesheet row').' for '.$period.
            ', totalling '.Formatters::money($total, $currency).'.';

        $detail = collect($rows)
            ->map(fn ($row) => sprintf(
                'WK %s  %-18s  %s  %s  %s hrs @ %s = %s (margin %s)',
                $row['week_no'] ?? '—',
                $row['worker'],
                $row['agency'],
                $row['shift_date'],
                Formatters::number($row['hours']),
                Formatters::money($row['rate'], $currency),
                Formatters::money($row['tsv'], $currency),
                Formatters::money($row['margin'], $currency),
            ))
            ->implode(PHP_EOL);

        return new BotReply(
            $answer,
            $detail,
            $rows,
            ['TSV by contact', 'Show TSV summary', 'Show my pipeline summary'],
            null,
            [],
            self::KEY,
        );
    }
}
