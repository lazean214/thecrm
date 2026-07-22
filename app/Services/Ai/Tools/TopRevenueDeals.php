<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TopRevenueDeals implements AiTool
{
    public function name(): string
    {
        return 'top_revenue_deals';
    }

    public function description(): string
    {
        return 'Retrieve the top deals ranked by value/revenue. Arguments: limit (integer, default 10).';
    }

    public function run(array $arguments, User $user): array
    {
        $validator = Validator::make($arguments, [
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $limit = (int) ($arguments['limit'] ?? 10);

        $deals = Deal::visibleTo($user)
            ->whereNotIn('stage', [
                DealStage::LOST->value,
            ])
            ->orderBy('amount', 'desc')
            ->limit($limit)
            ->get();

        return $deals->map(fn (Deal $deal) => [
            'id' => $deal->id,
            'name' => $deal->name,
            'amount' => $deal->amount,
            'stage' => $deal->stage?->value,
            'last_updated' => $deal->updated_at->toIso8601String(),
            'owner' => $deal->user?->name,
            'company' => $deal->primaryCompany() ? [
                'id' => $deal->primaryCompany()->id,
                'name' => $deal->primaryCompany()->name,
            ] : null,
        ])->toArray();
    }
}
