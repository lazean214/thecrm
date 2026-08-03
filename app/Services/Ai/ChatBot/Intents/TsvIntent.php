<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;

final class TsvIntent implements ChatIntent
{
    /**
     * @param  array<int, ChatIntent>  $children
     */
    public function __construct(private array $children) {}

    public function key(): string
    {
        return 'tsv';
    }

    public function label(): string
    {
        return 'TSV / Timesheet value';
    }

    public function description(): string
    {
        return 'Timesheet (TSV) value, hours and billers for a chosen period.';
    }

    public function keywords(): array
    {
        return [
            'tsv' => 3,
            'timesheet' => 3,
            'value' => 1,
            'amount' => 1,
            'billing' => 1,
        ];
    }

    public function children(): array
    {
        return $this->children;
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
        return (new TsvSummaryIntent)->handle($params, $user);
    }
}
