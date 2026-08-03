<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class DealsOverdueIntent implements ChatIntent
{
    public const KEY = 'deals.overdue';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Overdue follow-ups';
    }

    public function description(): string
    {
        return 'Deals that need follow-up based on stage-specific age rules.';
    }

    public function keywords(): array
    {
        return [
            'overdue follow-ups' => 7,
            'overdue follow ups' => 7,
            'overdue followups' => 7,
            'overdue' => 4,
            'follow-up' => 4,
            'follow up' => 4,
            'followups' => 4,
            'needs attention' => 4,
            'needing attention' => 4,
            'late' => 2,
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
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('stage', DealStage::DOC_SENT->value)
                        ->where(function ($sub) {
                            $sub->where('stage_updated_at', '<', now()->subHours(24))
                                ->orWhereNull('stage_updated_at');
                        });
                })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::DOC_SIGNED->value)
                            ->where(function ($sub) {
                                $sub->where('stage_updated_at', '<', now()->subDays(2))
                                    ->orWhereNull('stage_updated_at');
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::COMPLIANT->value)
                            ->where(function ($sub) {
                                $sub->whereNotExists(function ($existsQuery) {
                                    $existsQuery->selectRaw(1)
                                        ->from('activity_logs')
                                        ->whereColumn('activity_logs.deal_id', 'deals.id')
                                        ->where('activity_logs.created_at', '>=', now()->subDays(3));
                                });
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::READY_FOR_PAYMENT->value)
                            ->where(function ($sub) {
                                $sub->where('stage_updated_at', '<', now()->subDays(7))
                                    ->orWhereNull('stage_updated_at');
                            });
                    });
            })
            ->orderBy('stage_updated_at', 'asc')
            ->limit(20)
            ->get();

        if ($deals->isEmpty()) {
            return (new BotReply(
                'No overdue follow-ups — every active deal is on track.',
                null,
                [],
                ['Show my pipeline summary', 'List stalled deals'],
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
        $answer = 'You have '.Formatters::plural(count($rows), 'deal').' that '.(count($rows) > 1 ? 'need' : 'needs').
            " follow-up: {$names}".(count($rows) > 3 ? ' and more.' : '.');

        $detail = collect($rows)
            ->map(fn ($row) => $row['name'].' — '.Formatters::title($row['stage']).', '.
                Formatters::plural($row['days_in_stage'], 'day').' in stage ('.$row['contact'].', '.Formatters::money($row['amount'], $currency).')')
            ->implode(PHP_EOL);

        return (new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show deals by status', 'List stalled deals', 'Show top deals'],
            null,
            [],
            self::KEY,
        ))->withDealsUrl(route('deals'));
    }
}
