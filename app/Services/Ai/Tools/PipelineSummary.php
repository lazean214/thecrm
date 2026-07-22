<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PipelineSummary implements AiTool
{
    public function name(): string
    {
        return 'pipeline_summary';
    }

    public function description(): string
    {
        return 'Get a high-level summary of the sales pipeline, including total counts and monetary values of deals in each stage.';
    }

    public function run(array $arguments, User $user): array
    {
        $validator = Validator::make($arguments, []);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $summary = Deal::visibleTo($user)
            ->selectRaw('stage, count(*) as count, sum(amount) as total_amount')
            ->groupBy('stage')
            ->get();

        return $summary->map(fn ($row) => [
            'stage' => $row->stage instanceof DealStage ? $row->stage->value : (string) $row->stage,
            'count' => (int) $row->count,
            'total_amount' => (float) ($row->total_amount ?? 0),
        ])->toArray();
    }
}
