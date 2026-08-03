<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot\Contracts;

use App\Models\User;
use App\Services\Ai\ChatBot\BotReply;

interface ChatIntent
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /**
     * Keyword => weight pairs used by the intent resolver. A keyword may be a
     * single word or a phrase; both are matched against the normalized message.
     *
     * @return array<string, int>
     */
    public function keywords(): array;

    /**
     * @return array<int, ChatIntent>
     */
    public function children(): array;

    /**
     * Parameter keys this intent needs before it can produce an answer.
     *
     * @return array<int, string>
     */
    public function requires(): array;

    /**
     * The follow-up question asked when a required parameter is still missing.
     */
    public function question(): ?string;

    /**
     * Suggested answer chips shown alongside the follow-up question.
     *
     * @return array<int, string>
     */
    public function questionOptions(): array;

    /**
     * Produce the (summarized + detailed) answer for the given parameters.
     * Returns an "asking" BotReply when required parameters are missing.
     *
     * @param  array<string, mixed>  $params
     */
    public function handle(array $params, User $user): BotReply;
}
