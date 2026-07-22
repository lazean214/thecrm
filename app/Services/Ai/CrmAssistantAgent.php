<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[Temperature(0.1)]
#[MaxTokens(300)]
class CrmAssistantAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        protected string $systemPrompt,
        protected array $context
    ) {}

    public function instructions(): string
    {
        $contextStr = json_encode($this->context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return "{$this->systemPrompt}\n\nContext (includes user info, pre-fetched CRM data, and available tools):\n{$contextStr}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tool' => $schema->string()->nullable()->description('The name of the tool to execute, if any.'),
            'arguments' => $schema->object()->nullable()->description('Key-value arguments for the tool.'),
            'answer' => $schema->string()->nullable()->description('The final answer to the user\'s question (3 sentences max).'),
            'suggestions' => $schema->array()->items($schema->string())->description('Suggested follow-up action chips (e.g. ["Create tasks", "Send email"]).'),
        ];
    }
}
