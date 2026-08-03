<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class DealsStalledIntent implements ChatIntent
{
    public const KEY = 'deals.stalled';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Stalled deals';
    }

    public function description(): string
    {
        return 'Deals stalled in their current stage — doc sent after 24 hours, other active stages after 2 days.';
    }

    public function keywords(): array
    {
        return [
            'stalled deals' => 6,
            'stuck deals' => 5,
            'stalled' => 4,
            'no progress' => 4,
            'not moving' => 4,
            'stale' => 3,
            'stuck' => 2,
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
        $currency = $params['currency'] ?? 'gbp';

        $deals = Deal::visibleTo($user)
            ->withPrimaryRelations()
            ->whereIn('stage', [
                DealStage::DOC_SENT->value,
                DealStage::DOC_SIGNED->value,
                DealStage::COMPLIANT->value,
                DealStage::READY_FOR_PAYMENT->value,
            ])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('stage', DealStage::DOC_SENT->value)
                        ->where(function ($sub) {
                            $sub->where('stage_updated_at', '<', now()->subHours(24))
                                ->orWhereNull('stage_updated_at');
                        });
                })
                    ->orWhere(function ($q) {
                        $q->whereIn('stage', [
                            DealStage::DOC_SIGNED->value,
                            DealStage::COMPLIANT->value,
                            DealStage::READY_FOR_PAYMENT->value,
                        ])
                            ->where(function ($sub) {
                                $sub->where('stage_updated_at', '<', now()->subDays(2))
                                    ->orWhereNull('stage_updated_at');
                            });
                    });
            })
            ->orderBy('stage_updated_at', 'asc')
            ->limit(20)
            ->get();

        if ($deals->isEmpty()) {
            return (new BotReply(
                'No stalled deals — every deal is moving through its stage.',
                null,
                [],
                ['Show my pipeline summary', 'Any overdue follow-ups?'],
                null,
                [],
                self::KEY,
            ))->withDealsUrl(route('deals'));
        }

        $rows = $deals->map(fn (Deal $deal) => [
            'id' => $deal->id,
            'name' => $deal->name,
            'url' => route('deals.show', $deal),
            'amount' => (float) $deal->amount,
            'stage' => Formatters::stage($deal->stage),
            'days_in_stage' => (int) ($deal->stage_updated_at?->diffInDays(now()) ?? 0),
            'contact' => $deal->primaryContact() ? trim($deal->primaryContact()->first_name.' '.$deal->primaryContact()->last_name) : '—',
        ])->toArray();

        $names = collect($rows)->pluck('name')->take(3)->join(', ');
        $answer = 'You have '.Formatters::plural(count($rows), 'deal').' that '.(count($rows) > 1 ? 'have' : 'has').
            " not moved in their current stage: {$names}".(count($rows) > 3 ? ' and more.' : '.');

        $detail = collect($rows)
            ->map(fn ($row) => $row['name'].' — '.Formatters::title($row['stage']).', '.
                Formatters::plural($row['days_in_stage'], 'day').' in stage ('.$row['contact'].', '.Formatters::money($row['amount'], $currency).')')
            ->implode(PHP_EOL);

        return (new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show deals by status', 'Show top deals', 'Any overdue follow-ups?'],
            null,
            [],
            self::KEY,
        ))->withDealsUrl(route('deals'));
    }
}
