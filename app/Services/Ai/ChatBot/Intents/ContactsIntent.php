<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Intents;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;
use App\Services\Ai\ChatBot\Contracts\ChatIntent;

final class ContactsIntent implements ChatIntent
{
    /**
     * @param  array<int, ChatIntent>  $children
     */
    public function __construct(private array $children) {}

    public function key(): string
    {
        return 'contacts';
    }

    public function label(): string
    {
        return 'Contacts';
    }

    public function description(): string
    {
        return 'Active and inactive contacts for a chosen period.';
    }

    public function keywords(): array
    {
        return [
            'contacts' => 3,
            'contact' => 3,
            'people' => 2,
            'billers' => 2,
            'workers' => 2,
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
        return (new ContactsActiveIntent)->handle($params, $user);
    }
}
