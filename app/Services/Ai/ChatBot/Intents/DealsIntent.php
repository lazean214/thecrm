<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;

final class DealsIntent implements ChatIntent
{
    /**
     * @param  array<int, ChatIntent>  $children
     */
    public function __construct(private array $children) {}

    public function key(): string
    {
        return 'deals';
    }

    public function label(): string
    {
        return 'Deals';
    }

    public function description(): string
    {
        return 'Questions about deals: summary, status breakdown, top, stalled, overdue and recent deals.';
    }

    public function keywords(): array
    {
        return [
            'deals' => 2,
            'deal' => 2,
            'pipeline' => 3,
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
        return (new DealsSummaryIntent)->handle($params, $user);
    }
}
