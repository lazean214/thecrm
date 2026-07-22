<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StalledDeals implements AiTool
{
    public function name(): string
    {
        return 'stalled_deals';
    }

    public function description(): string
    {
        return 'Identify deals that have remained in their current stage for longer than X days without updates. Arguments: days (integer, default 14).';
    }

    public function run(array $arguments, User $user): array
    {
        $validator = Validator::make($arguments, [
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $days = (int) ($arguments['days'] ?? 7);

        $deals = Deal::visibleTo($user)
            ->whereIn('stage', [
                DealStage::DOC_SENT->value,
                DealStage::DOC_SIGNED->value,
                DealStage::COMPLIANT->value,
                DealStage::READY_FOR_PAYMENT->value,
            ])
            ->where(function ($query) use ($days) {
                $query->where('stage_updated_at', '<', now()->subDays($days))
                    ->orWhereNull('stage_updated_at');
            })
            ->orderBy('stage_updated_at', 'asc')
            ->limit(20)
            ->get();

        return $deals->map(fn (Deal $deal) => [
            'id' => $deal->id,
            'name' => $deal->name,
            'amount' => $deal->amount,
            'stage' => $deal->stage?->value,
            'stage_updated_at' => $deal->stage_updated_at?->toIso8601String(),
            'days_in_stage' => $deal->stage_updated_at ? $deal->stage_updated_at->diffInDays(now()) : 0,
            'contact' => $deal->primaryContact() ? [
                'name' => trim($deal->primaryContact()->first_name.' '.$deal->primaryContact()->last_name),
                'email' => $deal->primaryContact()->email,
            ] : null,
        ])->toArray();
    }
}
