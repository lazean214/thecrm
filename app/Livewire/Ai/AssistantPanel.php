<?php

declare(strict_types=1);

namespace App\Livewire\Ai;

use App\Services\Ai\AiCrmAssistant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class AssistantPanel extends Component
{
    public bool $isOpen = false;

    /**
     * @var array<int, array{role: string, content: string, suggestions?: array<int, string>}>
     */
    public array $messages = [];

    public string $input = '';

    public bool $isLoading = false;

    #[On('toggle-ai-assistant')]
    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;

        // Seed welcome message if conversation is empty
        if ($this->isOpen && empty($this->messages)) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Hello! I am your AI CRM Assistant. I can help you find contacts, summarize your pipeline, check for stalled or overdue deals, and more. What would you like to know?',
                'suggestions' => [
                    'Show pipeline summary',
                    'Any overdue follow-ups?',
                    'List stalled deals',
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

        // Add user message
        $this->messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        $this->input = '';
        $this->isLoading = true;

        try {
            $assistant = app(AiCrmAssistant::class);
            $result = $assistant->ask($question, auth()->user());

            $this->messages[] = [
                'role' => 'assistant',
                'content' => $result['answer'] ?? 'I could not find an answer or retrieve data for your request.',
                'suggestions' => $result['suggestions'] ?? [],
            ];
        } catch (AuthorizationException $e) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Access Denied: You do not have permission to execute this AI action.',
                'suggestions' => [],
            ];
        } catch (ValidationException $e) {
            $errors = implode(', ', array_flat($e->errors()));
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

    /**
     * Render the component view.
     */
    public function render()
    {
        return view('livewire.ai.assistant-panel');
    }
}
