<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\Remittance;
use App\Models\User;
use App\Services\Ai\ChatBot\Support\FiscalYear;
use Illuminate\Database\Eloquent\Builder;

trait ScopesRemittances
{
    /**
     * Remittance rows for the requested period. Defaults to the current
     * fiscal year, and sales team members only see rows they own.
     *
     * @param  array<string, mixed>  $params
     */
    private function remittanceQuery(array $params, User $user): Builder
    {
        $range = $params['date_range'] ?? null;

        $query = Remittance::query()
            ->with(['contact', 'company', 'owner'])
            ->whereNotNull('contact_id');

        if ($range === null) {
            $fy = FiscalYear::current();
            $query->whereBetween('we_date', [$fy['start'], $fy['end']]);
        } else {
            $query->whereBetween('we_date', [$range->from, $range->to]);
        }

        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            $query->where('deal_owner', $user->getKey());
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function periodLabel(array $params): string
    {
        return $params['date_range']?->label ?? 'this fiscal year';
    }
}
