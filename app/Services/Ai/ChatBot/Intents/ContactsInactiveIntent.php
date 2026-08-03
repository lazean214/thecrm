<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\Contact;
use App\Models\Remittance;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\DateRange;
use App\Services\Ai\ChatBot\Support\FiscalYear;
use App\Services\Ai\ChatBot\Support\Formatters;

final class ContactsInactiveIntent implements ChatIntent
{
    public const KEY = 'contacts.inactive';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Inactive contacts';
    }

    public function description(): string
    {
        return 'Contacts who billed this fiscal year but have not billed recently.';
    }

    public function keywords(): array
    {
        return [
            'inactive contacts' => 7,
            'inactive contact' => 6,
            'inactive billers' => 7,
            'inactive workers' => 7,
            'inactive' => 3,
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
        $fy = FiscalYear::current();
        $range = $params['date_range'] ?? new DateRange(
            'last 30 days',
            now()->copy()->subDays(30)->startOfDay(),
            now()->copy()->endOfDay(),
        );

        $base = function () use ($user) {
            $query = Remittance::query()->whereNotNull('contact_id');

            if ($user->isSalesTeam() && ! $user->isAdmin()) {
                $query->where('deal_owner', $user->getKey());
            }

            return $query;
        };

        $fyBillers = $base()->whereBetween('we_date', [$fy['start'], $fy['end']])
            ->pluck('contact_id')
            ->unique()
            ->filter();

        $activeBillers = $base()->whereBetween('we_date', [$range->from, $range->to])
            ->pluck('contact_id')
            ->unique()
            ->filter();

        $inactiveIds = $fyBillers->diff($activeBillers);

        if ($inactiveIds->isEmpty()) {
            return new BotReply(
                'There are no inactive contacts for '.$range->label.'.',
                null,
                [],
                ['Show active contacts', 'Show TSV this fiscal year', 'Show my pipeline summary'],
                null,
                [],
                self::KEY,
            );
        }

        $lastBillings = Remittance::query()
            ->whereIn('contact_id', $inactiveIds)
            ->whereBetween('we_date', [$fy['start'], $fy['end']])
            ->when($user->isSalesTeam() && ! $user->isAdmin(), fn ($query) => $query->where('deal_owner', $user->getKey()))
            ->get(['contact_id', 'we_date'])
            ->groupBy('contact_id')
            ->map(fn ($group) => $group->max('we_date'));

        $rows = Contact::query()
            ->whereIn('id', $inactiveIds)
            ->orderBy('first_name')
            ->get()
            ->map(function (Contact $contact) use ($lastBillings) {
                $last = $lastBillings->get($contact->getKey());

                return [
                    'contact_id' => $contact->getKey(),
                    'name' => trim($contact->first_name.' '.$contact->last_name),
                    'email' => $contact->email ?? '—',
                    'last_billed' => $last?->toDateString() ?? '—',
                ];
            })
            ->values()
            ->toArray();

        $answer = 'Out of your fiscal year billers, '.Formatters::plural(count($rows), 'contact').' '.
            (count($rows) === 1 ? 'has' : 'have').' not billed in '.$range->label.'.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['name'].' — last billed '.$row['last_billed'].' ('.$row['email'].')')
            ->implode(PHP_EOL);

        return new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show active contacts', 'Show TSV this fiscal year', 'Show my pipeline summary'],
            null,
            [],
            self::KEY,
        );
    }
}
