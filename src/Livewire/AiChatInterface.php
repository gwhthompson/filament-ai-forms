<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Livewire;

use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Services\StreamingResponseHandler;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

/**
 * Livewire component for AI-powered chat interface with streaming support.
 *
 * Provides a conversational UI for refining content with AI assistance.
 * Supports streaming responses for real-time feedback.
 */
class AiChatInterface extends Component
{
    /** @var array<int, array{role: string, content: string, timestamp: string}> */
    public array $messages = [];

    public bool $generating = false;

    public ?string $errorMessage = null;

    public string $userInput = '';

    public ?string $currentGeneratedContent = '';

    public string $streamingContent = '';

    #[Locked]
    public string $systemPrompt = '';

    #[Locked]
    public string $contextPrompt = '';

    #[Locked]
    public string $model = '';

    #[Locked]
    public string $regeneratePrompt = 'Generate fresh content based on the context.';

    #[Modelable]
    public string $content = '';

    public string $identifier = '';

    public function mount(
        string $currentContent = '',
        string $initialPrompt = '',
        string $systemPrompt = 'You are a helpful AI assistant.',
        string $contextPrompt = '',
        ?string $model = null,
        ?string $regeneratePrompt = null,
    ): void {
        $this->content = $currentContent;
        $this->systemPrompt = $systemPrompt;
        $this->contextPrompt = $contextPrompt;
        $defaultModel = config('filament-ai-forms.model', 'gpt-4.1-mini');
        $this->model = $model ?? (is_string($defaultModel) ? $defaultModel : 'gpt-4.1-mini');
        $this->regeneratePrompt = $regeneratePrompt ?? $this->regeneratePrompt;
        $this->messages = [];
        $this->generating = false;
        $this->errorMessage = null;
        $this->currentGeneratedContent = $currentContent;

        // Always add existing content as first assistant message (so AI knows what to refine)
        if ($currentContent !== '' && $currentContent !== '0') {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $currentContent,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Pre-fill user input with initial prompt (user clicks Send manually)
        if ($initialPrompt !== '' && $initialPrompt !== '0') {
            $this->userInput = $initialPrompt;
        }
    }

    public function sendMessage(): void
    {
        $this->validate([
            'userInput' => 'required|string|max:10000',
        ]);

        $userInput = trim($this->userInput);

        // Add user message and clear input immediately (Request 1)
        $this->messages[] = ['role' => 'user', 'content' => $userInput, 'timestamp' => now()->toIso8601String()];
        $this->userInput = '';

        // Defer streaming to separate request (Request 2)
        $this->js('$wire.performGeneration()');
    }

    public function deleteMessage(int $index): void
    {
        if (isset($this->messages[$index])) {
            unset($this->messages[$index]);
            $this->messages = array_values($this->messages); // Re-index

            // Update currentGeneratedContent to latest assistant message
            $this->currentGeneratedContent = $this->getLatestAssistantMessage();
            $this->content = $this->currentGeneratedContent;
        }
    }

    public function regenerate(): void
    {
        // Clear all messages for fresh generation
        $this->messages = [];
        $this->currentGeneratedContent = '';
        $this->content = '';
        $this->errorMessage = null;

        // Add a user message requesting fresh generation
        $this->messages[] = [
            'role' => 'user',
            'content' => $this->regeneratePrompt,
            'timestamp' => now()->toIso8601String(),
        ];

        // Defer streaming to separate request
        $this->js('$wire.performGeneration()');
    }

    public function performGeneration(): void
    {
        $this->generating = true;
        $this->errorMessage = null;
        $this->streamingContent = '';

        // Force render with $generating = true, then start streaming in next tick
        $this->js('setTimeout(() => $wire.startStreaming(), 50)');
    }

    public function startStreaming(): void
    {
        $this->performGenerationInternal($this->messages);
    }

    /** @param  array<int, array{role: string, content: string, timestamp: string}>  $messages */
    protected function performGenerationInternal(array $messages): void
    {
        try {
            $inputMessages = [];

            // Prepend brief context as first user message (AI sees this as background)
            if ($this->contextPrompt !== '') {
                $inputMessages[] = [
                    'role' => 'user',
                    'content' => $this->contextPrompt,
                ];
            }

            foreach ($messages as $message) {
                $inputMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ];
            }

            // Build request params from unified config
            $config = new AiGenerationConfig(
                model: $this->model,
                useWebSearch: false,
            );
            $requestParams = $config->toRequestParams($inputMessages);
            $requestParams['instructions'] = $this->systemPrompt;
            $requestParams['stream'] = true;

            $stream = OpenAI::responses()->createStreamed($requestParams);

            // Use unified streaming handler
            $handler = app(StreamingResponseHandler::class);

            $fullContent = $handler->handle(
                stream: $stream,
                onDelta: function (string $delta, string $accumulated): void {
                    $this->streamingContent .= $delta;

                    // Stream to static target for real-time updates
                    $this->stream(to: 'ai-streaming', content: $delta, replace: false);
                }
            );

            // Add completed message to chat history
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $fullContent,
                'timestamp' => now()->toIso8601String(),
            ];

            $this->currentGeneratedContent = $fullContent;
            $this->content = $fullContent;
            $this->streamingContent = '';
            $this->generating = false;

            // Dispatch generic event (parent can listen if needed)
            $this->dispatch('ai-content-generated', content: $fullContent);
        } catch (Throwable $throwable) {
            logger()->error('AI generation failed', [
                'error' => $throwable->getMessage(),
                'exception' => $throwable::class,
                'trace' => $throwable->getTraceAsString(),
                'messages_count' => count($inputMessages),
                'last_user_message' => end($inputMessages)['content'] ?? null,
            ]);
            $this->streamingContent = '';
            $this->errorMessage = 'Failed to generate response. Please try again.';
            $this->generating = false;
            $this->dispatch('ai-error', message: 'Failed to generate response. Please try again.');
        }
    }

    public function getLatestAssistantMessage(): string
    {
        for ($i = count($this->messages) - 1; $i >= 0; $i--) {
            if (($this->messages[$i]['role'] ?? '') === 'assistant') {
                return $this->messages[$i]['content'];
            }
        }

        return '';
    }

    public function render(): View
    {
        /** @var view-string $viewName */
        $viewName = 'filament-ai-forms::livewire.ai-chat-interface';

        return view($viewName);
    }
}
