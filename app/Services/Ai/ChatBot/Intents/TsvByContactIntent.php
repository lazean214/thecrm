<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class TsvByContactIntent implements ChatIntent
{
    use ScopesRemittances;

    public const KEY = 'tsv.by_contact';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'TSV by contact';
    }

    public function description(): string
    {
        return 'Timesheet value grouped by contact (worker / biller).';
    }

    public function keywords(): array
    {
        return [
            'by contact' => 6,
            'per contact' => 6,
            'by worker' => 6,
            'per worker' => 6,
            'by biller' => 6,
            'per biller' => 6,
            'top billers' => 6,
            'worker' => 2,
            'biller' => 2,
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

        $rows = $records->groupBy('contact_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'contact_id' => $first->contact_id,
                    'worker' => $first->contact ? trim($first->contact->first_name.' '.$first->contact->last_name) : '—',
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

        $top = $rows[0]['worker'];
        $answer = 'Your top biller for '.$period.' is '.$top.' at '.Formatters::money($rows[0]['tsv'], $currency).
            ', out of '.Formatters::plural(count($rows), 'contact').'.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['worker'].' — '.Formatters::money($row['tsv'], $currency).' ('.Formatters::plural((int) $row['hours'], 'hour').')')
            ->implode(PHP_EOL);

        return new BotReply(
            $answer,
            $detail,
            $rows,
            ['TSV by agency', 'TSV by owner', 'Show TSV summary'],
            null,
            [],
            self::KEY,
        );
    }
}
