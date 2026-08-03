<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;

final class HelpIntent implements ChatIntent
{
    public function key(): string
    {
        return 'help';
    }

    public function label(): string
    {
        return 'Help';
    }

    public function description(): string
    {
        return 'Explain what the CRM ChatBot can answer.';
    }

    public function keywords(): array
    {
        return [
            'what can you do' => 5,
            'what do you do' => 5,
            'how can you help' => 5,
            'how do you work' => 4,
            'who are you' => 4,
            'capabilities' => 3,
            'commands' => 3,
            'help' => 3,
            'options' => 2,
            'hello' => 2,
            'hi' => 2,
            'hey' => 2,
            'greetings' => 2,
        ];
    }

    public function children(): array
    {
        return [];
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
        $answer = "I'm your CRM ChatBot. I give predefined answers to questions about:\n".
            "• Deals — pipeline summary, deals by status, top deals, stalled and overdue deals, recent deals.\n".
            "• TSV / Timesheet value — total value, hours, billers and companies for a chosen period.\n".
            "• Contacts — active and inactive contacts for a chosen date range.\n\n".
            'Pick a suggestion below or ask me a question in your own words.';

        return new BotReply(
            $answer,
            null,
            [],
            [
                'Show my pipeline summary',
                'Show deals by status',
                'Show TSV this fiscal year',
                'Active contacts this fiscal year',
                'Inactive contacts last 30 days',
            ],
            null,
            [],
            $this->key(),
        );
    }
}
