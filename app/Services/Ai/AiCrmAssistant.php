<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiAssistantLog;
use App\Models\User;
use App\Services\Ai\Tools\AiTool;
use App\Services\Ai\Tools\CompanyLookup;
use App\Services\Ai\Tools\ContactLookup;
use App\Services\Ai\Tools\OverdueFollowups;
use App\Services\Ai\Tools\PipelineSummary;
use App\Services\Ai\Tools\StalledDeals;
use App\Services\Ai\Tools\TopRevenueDeals;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AiCrmAssistant
{
    /**
     * @var array<string, AiTool>
     */
    protected array $tools = [];

    protected CrmContextGatherer $contextGatherer;

    public function __construct(?CrmContextGatherer $contextGatherer = null)
    {
        $this->contextGatherer = $contextGatherer ?? new CrmContextGatherer;

        $this->tools = [
            'overdue_followups' => new OverdueFollowups,
            'pipeline_summary' => new PipelineSummary,
            'stalled_deals' => new StalledDeals,
            'top_revenue_deals' => new TopRevenueDeals,
            'contact_lookup' => new ContactLookup,
            'company_lookup' => new CompanyLookup,
        ];
    }

    /**
     * Receive user question and authenticated user, execute prompt/tools and return final result.
     *
     * The system first queries CRM models to gather relevant context, then sends that
     * context alongside the question to the LLM for processing.
     *
     * @return array{tool: ?string, arguments: array, answer: ?string, suggestions: array}
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function ask(string $question, User $user): array
    {
        $totalDurationStart = microtime(true);
        $totalTokens = 0;

        // Step 1: Query CRM models first to gather relevant context
        $crmContext = $this->contextGatherer->gather($question, $user);
        $crmData = $crmContext['data'];
        $contextSummary = $crmContext['summary'];

        // Build context object for the agent
        $context = [
            'user' => [
                'id' => $user->id,
                'role' => $user->isAdmin() ? 'admin' : ($user->isSalesTeam() ? 'sales' : ($user->isComplianceTeam() ? 'compliance' : 'user')),
                'team_ids' => $user->teams->pluck('id')->toArray(),
            ],
            'current_datetime' => now()->toIso8601String(),
            'crm_data' => $crmData,
            'available_tools' => collect($this->tools)->map(fn (AiTool $tool) => [
                'name' => $tool->name(),
                'description' => $tool->description(),
            ])->values()->toArray(),
        ];

        $systemPrompt = "You are the CRM AI assistant.\n".
            "IMPORTANT: CRM data has been pre-fetched from the database and is provided in the context below.\n".
            "Use this pre-fetched data to answer the user's question directly whenever possible.\n".
            "Only request a tool call if the pre-fetched data does not contain what you need.\n".
            "Never invent data.\n".
            "If additional data is needed, return only the JSON tool request.\n".
            'Answer in 3 sentences max. Be specific with numbers and names from the data.';

        $currentPrompt = "User Question: {$question}\n\n".
            "Pre-fetched CRM Context ({$contextSummary}):\n".
            json_encode($crmData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $activeTool = null;

        for ($step = 1; $step <= 2; $step++) {
            $agent = new CrmAssistantAgent($systemPrompt, $context);

            $response = $agent->prompt($currentPrompt);

            // Track tokens
            $tokensUsed = ($response->usage->promptTokens ?? 0) + ($response->usage->completionTokens ?? 0);
            $totalTokens += $tokensUsed;

            $data = $response->toArray();
            $requestedTool = $data['tool'] ?? null;
            $arguments = $data['arguments'] ?? [];

            if ($requestedTool && isset($this->tools[$requestedTool])) {
                $activeTool = $requestedTool;

                // Authorize tool access using Policy
                $policyMethod = 'use'.str_replace('_', '', ucwords($requestedTool, '_'));
                if (! Gate::forUser($user)->allows($policyMethod, self::class)) {
                    $totalDuration = (int) ((microtime(true) - $totalDurationStart) * 1000);
                    AiAssistantLog::create([
                        'user_id' => $user->id,
                        'tool' => $requestedTool,
                        'duration' => $totalDuration,
                        'token_usage' => $totalTokens,
                        'question' => $question,
                        'response' => 'Unauthorized access to tool '.$requestedTool,
                    ]);

                    throw new AuthorizationException('This action is unauthorized.');
                }

                // Execute the tool
                try {
                    $toolInstance = $this->tools[$requestedTool];
                    $toolResult = $toolInstance->run($arguments, $user);
                } catch (ValidationException $e) {
                    $totalDuration = (int) ((microtime(true) - $totalDurationStart) * 1000);
                    AiAssistantLog::create([
                        'user_id' => $user->id,
                        'tool' => $requestedTool,
                        'duration' => $totalDuration,
                        'token_usage' => $totalTokens,
                        'question' => $question,
                        'response' => 'Validation error: '.json_encode($e->errors()),
                    ]);
                    throw $e;
                }

                // Compile turn 2 prompt with tool results
                $currentPrompt = "User Question: {$question}\n\n".
                    "Pre-fetched CRM Context: {$contextSummary}\n\n".
                    "Tool '{$requestedTool}' was executed with arguments: ".json_encode($arguments)."\n".
                    'Additional Result: '.json_encode(array_slice($toolResult, 0, 20))."\n\n".
                    "Use all available data above to answer the user's question.";

                continue;
            }

            // We have a final answer
            $totalDuration = (int) ((microtime(true) - $totalDurationStart) * 1000);

            AiAssistantLog::create([
                'user_id' => $user->id,
                'tool' => $activeTool,
                'duration' => $totalDuration,
                'token_usage' => $totalTokens,
                'question' => $question,
                'response' => $data['answer'] ?? '',
            ]);

            return [
                'tool' => $requestedTool,
                'arguments' => $arguments,
                'answer' => $data['answer'] ?? null,
                'suggestions' => $data['suggestions'] ?? [],
            ];
        }

        // Fallback
        $totalDuration = (int) ((microtime(true) - $totalDurationStart) * 1000);
        AiAssistantLog::create([
            'user_id' => $user->id,
            'tool' => $activeTool,
            'duration' => $totalDuration,
            'token_usage' => $totalTokens,
            'question' => $question,
            'response' => 'Max steps exceeded.',
        ]);

        return [
            'tool' => null,
            'arguments' => [],
            'answer' => 'Sorry, I encountered an issue processing your request.',
            'suggestions' => [],
        ];
    }
}
