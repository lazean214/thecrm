<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class TsvByDealIntent implements ChatIntent
{
    use ScopesRemittances;

    public const KEY = 'tsv.by_deal';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'TSV by deal owner';
    }

    public function description(): string
    {
        return 'Timesheet value grouped by deal owner (CID).';
    }

    public function keywords(): array
    {
        return [
            'by deal' => 6,
            'per deal' => 6,
            'by owner' => 6,
            'per owner' => 6,
            'by cid' => 6,
            'per cid' => 6,
            'deal owner' => 5,
            'owner' => 2,
            'cid' => 2,
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

        $rows = $records->groupBy('deal_owner')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'deal_owner' => $first->deal_owner,
                    'owner' => $first->owner?->name ?? '—',
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

        $top = $rows[0]['owner'];
        $answer = 'Your top deal owner for '.$period.' is '.$top.' at '.Formatters::money($rows[0]['tsv'], $currency).
            ', out of '.Formatters::plural(count($rows), 'owner').'.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['owner'].' — '.Formatters::money($row['tsv'], $currency).' ('.Formatters::plural((int) $row['hours'], 'hour').')')
            ->implode(PHP_EOL);

        return new BotReply(
            $answer,
            $detail,
            $rows,
            ['TSV by contact', 'TSV by agency', 'Show TSV summary'],
            null,
            [],
            self::KEY,
        );
    }
}
