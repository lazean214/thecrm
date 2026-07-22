<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContactLookup implements AiTool
{
    public function name(): string
    {
        return 'contact_lookup';
    }

    public function description(): string
    {
        return 'Look up contact details by name, email, phone, or direct ID. Arguments: search (string, optional), id (integer, optional).';
    }

    public function run(array $arguments, User $user): array
    {
        $validator = Validator::make($arguments, [
            'search' => ['nullable', 'string', 'min:1'],
            'id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (! isset($arguments['id']) && ! isset($arguments['search'])) {
            return [];
        }

        $query = Contact::query()->with(['companies', 'deals']);

        // Scope queries to the authenticated user/team
        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            $query->whereHas('deals', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if (isset($arguments['id'])) {
            $query->where('id', $arguments['id']);
        } else {
            $search = $arguments['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $contacts = $query->limit(20)->get();

        return $contacts->map(fn (Contact $contact) => [
            'id' => $contact->id,
            'name' => trim($contact->first_name.' '.$contact->last_name),
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'city' => $contact->city,
            'country' => $contact->country,
            'companies' => $contact->companies->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->toArray(),
            'deals' => $contact->deals->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'amount' => $d->amount,
                'stage' => $d->stage?->value,
            ])->toArray(),
        ])->toArray();
    }
}
