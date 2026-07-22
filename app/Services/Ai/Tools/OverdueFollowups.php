<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OverdueFollowups implements AiTool
{
    public function name(): string
    {
        return 'overdue_followups';
    }

    public function description(): string
    {
        return 'Retrieve active deals that need follow-up based on stage-specific rules (doc sent > 24h, doc signed > 2d, compliant with no activity/comments > 3d, ready for payment > 7d).';
    }

    public function run(array $arguments, User $user): array
    {
        $validator = Validator::make($arguments, []);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $deals = Deal::visibleTo($user)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('stage', DealStage::DOC_SENT->value)
                        ->where(function ($sub) {
                            $sub->where('stage_updated_at', '<', now()->subHours(24))
                                ->orWhereNull('stage_updated_at');
                        });
                })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::DOC_SIGNED->value)
                            ->where(function ($sub) {
                                $sub->where('stage_updated_at', '<', now()->subDays(2))
                                    ->orWhereNull('stage_updated_at');
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::COMPLIANT->value)
                            ->where(function ($sub) {
                                $sub->whereNotExists(function ($existsQuery) {
                                    $existsQuery->selectRaw(1)
                                        ->from('activity_logs')
                                        ->whereColumn('activity_logs.deal_id', 'deals.id')
                                        ->where('activity_logs.created_at', '>=', now()->subDays(3));
                                });
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::READY_FOR_PAYMENT->value)
                            ->where(function ($sub) {
                                $sub->where('stage_updated_at', '<', now()->subDays(7))
                                    ->orWhereNull('stage_updated_at');
                            });
                    });
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
