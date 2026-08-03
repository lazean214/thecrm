<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class DealsStatusIntent implements ChatIntent
{
    public const KEY = 'deals.status';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Deal status';
    }

    public function description(): string
    {
        return 'Count and value of active (open) or lost deals, optionally for a date range.';
    }

    public function keywords(): array
    {
        return [
            'deal status' => 6,
            'deals by status' => 6,
            'by status' => 5,
            'status' => 3,
            'active deals' => 6,
            'lost deals' => 6,
            'open deals' => 5,
            'won deals' => 5,
            'active' => 2,
            'lost' => 2,
            'open' => 1,
            'deals' => 1,
            'deal' => 1,
        ];
    }

    public function children(): array
    {
        return [];
    }

    public function requires(): array
    {
        return ['deal_status'];
    }

    public function question(): ?string
    {
        return 'Which deals would you like to see? **Active** deals (open, not yet paid) or **lost** deals?';
    }

    public function questionOptions(): array
    {
        return [
            'Show active deals',
            'Show lost deals',
            'Show active deals in the last 30 days',
        ];
    }

    public function handle(array $params, User $user): BotReply
    {
        $status = $params['deal_status'] ?? null;

        if ($status === null) {
            return BotReply::asking($this->question(), $this->questionOptions(), self::KEY);
        }

        $range = $params['date_range'] ?? null;
        $currency = $params['currency'] ?? 'gbp';

        $query = Deal::visibleTo($user)
            ->withPrimaryRelations();

        if ($status === 'lost') {
            $query->where('stage', DealStage::LOST->value);
        } else {
            $query->whereNotIn('stage', [DealStage::LOST->value, DealStage::PAID->value]);
        }

        if ($range !== null) {
            $query->whereBetween('created_at', [$range->from, $range->to]);
        }

        $deals = $query->orderByDesc('amount')->limit(50)->get();

        $dealsUrl = $status === 'lost'
            ? route('deals', ['stage' => DealStage::LOST->value])
            : route('deals');

        if ($deals->isEmpty()) {
            return (new BotReply(
                'There are no '.$status.' deals'.($range !== null ? ' for '.$range->label : '').'.',
                null,
                [],
                ['Show my pipeline summary', 'Show deals by status'],
                null,
                [],
                self::KEY,
            ))->withDealsUrl($dealsUrl);
        }

        $total = (float) $deals->sum('amount');

        $rows = $deals->map(fn (Deal $deal) => [
            'id' => $deal->id,
            'name' => $deal->name,
            'url' => route('deals.show', $deal),
            'amount' => (float) $deal->amount,
            'stage' => Formatters::stage($deal->stage),
            'consultant' => $deal->consultant_name ?? $deal->user?->name ?? '—',
        ])->toArray();

        $answer = 'You have '.Formatters::plural($deals->count(), $status.' deal').' totalling '.
            Formatters::money($total, $currency).($range !== null ? ' for '.$range->label : '').'.';

        $detail = collect($rows)
            ->map(fn ($row) => '#'.$row['id'].' '.$row['name'].' — '.Formatters::title($row['stage']).', '.
                Formatters::money($row['amount'], $currency).' ('.$row['consultant'].')')
            ->implode(PHP_EOL);

        return (new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show active deals', 'Show lost deals', 'Show my pipeline summary'],
            null,
            [],
            self::KEY,
        ))->withDealsUrl($dealsUrl);
    }
}
