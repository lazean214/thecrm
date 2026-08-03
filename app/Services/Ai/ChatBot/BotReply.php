<?php

declare(strict_types=1);

namespace App\Services\Ai\ChatBot;

final class BotReply
{
    /**
     * @param  array<int, array<string, mixed>>  $detailRows
     * @param  array<int, string>  $suggestions
     * @param  array<int, string>  $questionOptions
     */
    public function __construct(
        public readonly string $answer,
        public readonly ?string $detail = null,
        public readonly array $detailRows = [],
        public readonly array $suggestions = [],
        public readonly ?string $question = null,
        public readonly array $questionOptions = [],
        public readonly ?string $intent = null,
        public readonly ?string $dealsUrl = null,
    ) {}

    /**
     * @param  array<int, string>  $options
     */
    public static function asking(string $question, array $options, ?string $intent = null): self
    {
        return new self(
            answer: $question,
            suggestions: $options,
            question: $question,
            questionOptions: $options,
            intent: $intent,
        );
    }

    public function withIntent(string $intent): self
    {
        return new self(
            $this->answer,
            $this->detail,
            $this->detailRows,
            $this->suggestions,
            $this->question,
            $this->questionOptions,
            $intent,
            $this->dealsUrl,
        );
    }

    public function withDealsUrl(?string $dealsUrl): self
    {
        return new self(
            $this->answer,
            $this->detail,
            $this->detailRows,
            $this->suggestions,
            $this->question,
            $this->questionOptions,
            $this->intent,
            $dealsUrl,
        );
    }

    public function isAsking(): bool
    {
        return $this->question !== null;
    }
}
