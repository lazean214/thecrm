<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class DealsSummaryIntent implements ChatIntent
{
    public const KEY = 'deals.summary';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Deals summary';
    }

    public function description(): string
    {
        return 'Total deal count and value with a per-status breakdown.';
    }

    public function keywords(): array
    {
        return [
            'pipeline' => 3,
            'deals overview' => 4,
            'how many deals' => 4,
            'deal count' => 4,
            'total deals' => 4,
            'deals summary' => 4,
            'summary' => 2,
            'overview' => 2,
            'deals' => 2,
            'deal' => 2,
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
        $range = $params['date_range'] ?? null;

        $byStage = Deal::visibleTo($user)
            ->whereNotIn('stage', [DealStage::LOST->value])
            ->when($range !== null, fn ($query) => $query->whereBetween('created_at', [$range->from, $range->to]))
            ->selectRaw('stage, count(*) as count, sum(amount) as total_amount')
            ->groupBy('stage')
            ->get()
            ->map(fn ($row) => [
                'stage' => Formatters::stage($row->stage),
                'count' => (int) $row->count,
                'total_amount' => (float) ($row->total_amount ?? 0),
            ])
            ->sortBy('stage')
            ->values()
            ->toArray();

        $totalCount = array_sum(array_column($byStage, 'count'));
        $totalValue = array_sum(array_column($byStage, 'total_amount'));

        $period = $range !== null ? ' for '.$range->label : ' all-time';

        $answer = 'You have '.Formatters::plural($totalCount, 'open deal').' totalling '.
            Formatters::money($totalValue, $params['currency'] ?? 'gbp')." in your pipeline{$period}.";

        $detail = collect($byStage)
            ->map(fn ($row) => sprintf(
                '%-18s %-6s deals  %s',
                Formatters::title($row['stage']),
                Formatters::number($row['count']),
                Formatters::money($row['total_amount'], $params['currency'] ?? 'gbp'),
            ))
            ->implode(PHP_EOL);

        return (new BotReply(
            $answer,
            $detail === '' ? null : $detail,
            $byStage,
            ['Show deals by status', 'Show top deals', 'List stalled deals', 'Show TSV this fiscal year'],
            null,
            [],
            self::KEY,
        ))->withDealsUrl(route('deals'));
    }
}
