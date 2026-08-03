<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;

final class ReportsIntent implements ChatIntent
{
    /**
     * @param  array<int, ChatIntent>  $children
     */
    public function __construct(private array $children) {}

    public function key(): string
    {
        return 'reports';
    }

    public function label(): string
    {
        return 'Reports';
    }

    public function description(): string
    {
        return 'Generate TSV / timesheet value reports and active or inactive contact reports.';
    }

    public function keywords(): array
    {
        return [
            'report' => 3,
            'reports' => 4,
            'reporting' => 2,
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
        return BotReply::asking(
            'Which report would you like? I can produce a **TSV / timesheet value** report or an **active / inactive contacts** report.',
            [
                'Show TSV this fiscal year',
                'Show active contacts',
                'Show inactive contacts',
            ],
            self::KEY,
        );
    }
}
