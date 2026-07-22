<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompanyLookup implements AiTool
{
    public function name(): string
    {
        return 'company_lookup';
    }

    public function description(): string
    {
        return 'Look up company details by name, email, domain, phone, or direct ID. Arguments: search (string, optional), id (integer, optional).';
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

        $query = Company::query()->with(['contacts', 'deals']);

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
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $companies = $query->limit(20)->get();

        return $companies->map(fn (Company $company) => [
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'domain' => $company->domain,
            'phone' => $company->phone,
            'contacts' => $company->contacts->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim($c->first_name.' '.$c->last_name),
                'email' => $c->email,
            ])->toArray(),
            'deals' => $company->deals->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'amount' => $d->amount,
                'stage' => $d->stage?->value,
            ])->toArray(),
        ])->toArray();
    }
}
