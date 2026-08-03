<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\Deal;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class DealsRecentIntent implements ChatIntent
{
    public const KEY = 'deals.recent';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Recent deals';
    }

    public function description(): string
    {
        return 'The most recently added deals in the pipeline.';
    }

    public function keywords(): array
    {
        return [
            'recent deals' => 6,
            'recent deal' => 5,
            'newest deals' => 5,
            'latest deals' => 5,
            'recently added' => 5,
            'most recent' => 4,
            'recent' => 3,
            'newest' => 2,
            'latest' => 2,
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
        $limit = (int) ($params['limit'] ?? 5);
        $currency = $params['currency'] ?? 'gbp';

        $deals = Deal::visibleTo($user)
            ->withPrimaryRelations()
            ->latest()
            ->limit(max(1, min($limit, 25)))
            ->get();

        if ($deals->isEmpty()) {
            return (new BotReply(
                'There are no deals in your pipeline yet.',
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
            'date_logged' => $deal->date_logged ?? $deal->created_at,
            'company' => $deal->primaryCompany()?->name ?? '—',
        ])->toArray();

        $names = collect($rows)->pluck('name')->join(', ');
        $answer = 'Your '.Formatters::plural($deals->count(), 'most recent deal').': '.$names.'.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['name'].' — '.Formatters::title($row['stage']).', '.
                Formatters::money($row['amount'], $currency).' ('.($row['date_logged']?->toDateString() ?? '—').', '.$row['company'].')')
            ->implode(PHP_EOL);

        return (new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show top deals', 'Show deals by status', 'Show my pipeline summary'],
            null,
            [],
            self::KEY,
        ))->withDealsUrl(route('deals'));
    }
}
