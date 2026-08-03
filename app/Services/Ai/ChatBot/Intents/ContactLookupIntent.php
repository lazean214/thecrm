<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\Contact;
use App\Models\Remittance;
use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;
use App\Services\Ai\ChatBot\Support\FiscalYear;
use App\Services\Ai\ChatBot\Support\Formatters;

final class ContactLookupIntent implements ChatIntent
{
    public const KEY = 'contact.lookup';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Contact lookup';
    }

    public function description(): string
    {
        return 'Look up a contact by name, email or phone.';
    }

    public function keywords(): array
    {
        return [
            'find contact' => 7,
            'search contact' => 7,
            'lookup contact' => 7,
            'contact lookup' => 7,
            'who is' => 6,
            "who's" => 6,
            'find someone' => 5,
            'search for a contact' => 6,
            'lookup' => 3,
            'email address' => 3,
            'phone number' => 3,
            'contact' => 1,
            'person' => 1,
        ];
    }

    public function children(): array
    {
        return [];
    }

    public function requires(): array
    {
        return ['query'];
    }

    public function question(): ?string
    {
        return 'Who would you like me to look up? Tell me a name, email or phone number.';
    }

    public function questionOptions(): array
    {
        return [
            'Find contact by name',
            'Find contact by email',
            'Show me my active contacts',
        ];
    }

    public function handle(array $params, User $user): BotReply
    {
        $query = $params['query'] ?? null;

        if ($query === null || $query === '') {
            return BotReply::asking($this->question(), $this->questionOptions(), self::KEY);
        }

        $contacts = Contact::query()
            ->with(['companies', 'deals'])
            ->when($user->isSalesTeam() && ! $user->isAdmin(), function ($q) use ($user) {
                $q->whereHas('deals', fn ($deals) => $deals->where('user_id', $user->getKey()));
            })
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        if ($contacts->isEmpty()) {
            return new BotReply(
                "I couldn't find a contact matching \"{$query}\".",
                null,
                [],
                ['Find contact by email', 'Show my active contacts', 'Show my pipeline summary'],
                null,
                [],
                self::KEY,
            );
        }

        $fy = FiscalYear::current();
        $rows = $contacts->map(function (Contact $contact) use ($fy) {
            $active = Remittance::query()
                ->where('contact_id', $contact->getKey())
                ->whereBetween('we_date', [$fy['start'], $fy['end']])
                ->exists();

            return [
                'id' => $contact->getKey(),
                'name' => trim($contact->first_name.' '.$contact->last_name),
                'email' => $contact->email ?? '—',
                'phone' => $contact->phone ?? '—',
                'active' => $active,
                'companies' => $contact->companies->pluck('name')->join(', ') ?: '—',
                'deals' => $contact->deals->count(),
            ];
        })->toArray();

        $first = $rows[0];
        $answer = $first['name'].' — '.$first['email'].($first['phone'] !== '—' ? ', '.$first['phone'] : '').
            '. '.($first['active'] ? 'Active' : 'Inactive').' this fiscal year, '.Formatters::plural($first['deals'], 'deal').'.';

        $detail = collect($rows)
            ->map(fn ($row) => $row['name'].' — '.$row['email'].' ('.$row['phone'].'), '.
                ($row['active'] ? 'active' : 'inactive').', '.$row['companies'])
            ->implode(PHP_EOL);

        return new BotReply(
            $answer,
            $detail,
            $rows,
            ['Show my active contacts', 'Show my pipeline summary', 'Show TSV this fiscal year'],
            null,
            [],
            self::KEY,
        );
    }
}
