<?php

declare(strict_types=1);

namespace App\Livewire\Ai;

use App\Services\Ai\ChatBot\CrmChatBot;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class AssistantPanel extends Component
{
    public bool $isOpen = false;

    /**
     * @var array<int, array{role: string, content: string, suggestions?: array<int, string>, dealsUrl?: ?string}>
     */
    public array $messages = [];

    public string $input = '';

    public bool $isLoading = false;

    #[On('toggle-ai-assistant')]
    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;

        if ($this->isOpen && empty($this->messages)) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Hello! I am your CRM ChatBot. I can summarise your pipeline, report on deal status, stalled and overdue follow-ups, TSV / timesheet value, and active or inactive contacts. What would you like to know?',
                'suggestions' => [
                    'Show me the deals',
                    'Show deal status',
                    'Show top deals by revenue',
                    'Any stalled deals?',
                    'What is the TSV value?',
                    'Show me the timesheet details',
                    'Active contacts',
                    'Inactive contacts',
                    'Show reports',
                    'Need help',
                ],
            ];
        }
    }

    public function sendMessage(): void
    {
        $question = trim($this->input);

        if ($question === '') {
            return;
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        $this->input = '';
        $this->isLoading = true;

        try {
            $result = app(CrmChatBot::class)->ask($question, auth()->user());

            $this->messages[] = [
                'role' => 'assistant',
                'content' => $result->answer ?? 'I could not find an answer for your request.',
                'suggestions' => $result->suggestions,
                'dealsUrl' => $result->dealsUrl,
            ];
        } catch (ValidationException $e) {
            $errors = implode(', ', Arr::flatten($e->errors()));
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Validation Error: '.$errors,
                'suggestions' => [],
            ];
        } catch (\Exception $e) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'An error occurred while processing your request: '.$e->getMessage(),
                'suggestions' => [],
            ];
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectSuggestion(string $suggestion): void
    {
        $this->input = $suggestion;
        $this->sendMessage();
    }

    public function clearChat(): void
    {
        $this->messages = [];
        $this->isOpen = false;
        $this->toggle();
    }

    public function render()
    {
        return view('livewire.ai.assistant-panel');
    }
}
