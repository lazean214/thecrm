<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class DealsTopIntent implements ChatIntent
{
    public const KEY = 'deals.top';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Top deals';
    }

    public function description(): string
    {
        return 'The highest-value open deals in the pipeline.';
    }

    public function keywords(): array
    {
        return [
            'top deals' => 6,
            'top deal' => 5,
            'biggest deals' => 5,
            'biggest deal' => 5,
            'largest deals' => 5,
            'largest deal' => 5,
            'highest value deals' => 6,
            'most valuable deals' => 6,
            'revenue deals' => 5,
            'top' => 2,
            'revenue' => 2,
            'biggest' => 2,
            'largest' => 2,
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
        $limit = (int) ($params['limit'] ?? 10);
        $currency = $params['currency'] ?? 'gbp';

        $deals = Deal::visibleTo($user)
            ->withPrimaryRelations()
            ->whereNotIn('stage', [DealStage::LOST->value])
            ->orderByDesc('amount')
            ->limit(max(1, min($limit, 25)))
            ->get();

        if ($deals->isEmpty()) {
            return (new BotReply(
                'There are no open deals in your pipeline yet.',
                null,
                [],
                ['Show my pipeline summary', 'Show deals by status'],
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
            'owner' => $deal->user?->name ?? '—',
            'company' => $deal->primaryCompany()?->name ?? '—',
        ])->toArray();

        $top = $deals->first();
        $total = array_sum(array_column($rows, 'amount'));

        $answer = 'Your biggest open deal is '.$top->name.' at '.Formatters::money($top->amount, $currency).' ('.
            Formatters::title($top->stage).'). Your top '.count($rows).' deals total '.Formatters::money($total, $currency).'.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['name'].' — '.Formatters::money($row['amount'], $currency).' ('.
                Formatters::title($row['stage']).', '.$row['owner'].', '.$row['company'].')')
            ->implode(PHP_EOL);

        return (new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show deals by status', 'Show my pipeline summary', 'Show TSV this fiscal year'],
            null,
            [],
            self::KEY,
        ))->withDealsUrl(route('deals'));
    }
}
