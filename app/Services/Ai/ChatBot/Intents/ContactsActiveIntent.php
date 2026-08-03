<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\Formatters;

final class ContactsActiveIntent implements ChatIntent
{
    use ScopesRemittances;

    public const KEY = 'contacts.active';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Active contacts';
    }

    public function description(): string
    {
        return 'Contacts with timesheet activity in a chosen period.';
    }

    public function keywords(): array
    {
        return [
            'active contacts' => 7,
            'active contact' => 6,
            'active billers' => 7,
            'active workers' => 7,
            'active' => 2,
            'contacts' => 2,
            'contact' => 2,
            'billers' => 2,
            'workers' => 2,
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

        if ($records->isEmpty()) {
            return new BotReply(
                "There are no active contacts for {$period}.",
                null,
                [],
                ['Show inactive contacts', 'Show TSV this fiscal year', 'Show my pipeline summary'],
                null,
                [],
                self::KEY,
            );
        }

        $rows = $records->groupBy('contact_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'contact_id' => $first->contact_id,
                    'name' => $first->contact ? trim($first->contact->first_name.' '.$first->contact->last_name) : '—',
                    'email' => $first->contact?->email ?? '—',
                    'tsv' => (float) $group->sum('amount'),
                ];
            })
            ->sortByDesc('tsv')
            ->values()
            ->take(20)
            ->toArray();

        $total = array_sum(array_column($rows, 'tsv'));
        $answer = 'You have '.Formatters::plural(count($rows), 'active contact').' for '.$period.
            ', totalling '.Formatters::money($total, $currency).' in timesheet value.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['name'].' — '.Formatters::money($row['tsv'], $currency).' ('.$row['email'].')')
            ->implode(PHP_EOL);

        return new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show inactive contacts', 'Show TSV by contact', 'Show my pipeline summary'],
            null,
            [],
            self::KEY,
        );
    }
}
