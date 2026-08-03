<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot;

use App\Models\User;
use Illuminate\Support\Facades\Session;

final class BotState
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public ?string $pendingIntent = null,
        public array $params = [],
    ) {}

    public static function fromSession(User $user): self
    {
        $data = Session::get(self::sessionKey($user), []);

        return new self(
            pendingIntent: $data['pending_intent'] ?? null,
            params: $data['params'] ?? [],
        );
    }

    public static function sessionKey(User $user): string
    {
        return 'crm_chatbot_state_'.$user->getKey();
    }

    public function save(User $user): void
    {
        Session::put(self::sessionKey($user), [
            'pending_intent' => $this->pendingIntent,
            'params' => $this->params,
        ]);
    }

    public function clear(User $user): void
    {
        Session::forget(self::sessionKey($user));

        $this->pendingIntent = null;
        $this->params = [];
    }
}
