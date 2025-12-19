<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Livewire\AiChatInterface;
use Gwhthompson\FilamentAiForms\Tests\Traits\MocksOpenAi;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Responses;

use function Pest\Livewire\livewire;

covers(AiChatInterface::class);

uses(MocksOpenAi::class);

describe('AiChatInterface', function (): void {
    describe('mount behavior', function (): void {
        it('mounts with default values', function (): void {
            livewire(AiChatInterface::class)
                ->assertSet('messages', [])
                ->assertSet('generating', false)
                ->assertSet('userInput', '')
                ->assertSet('content', '');
        });

        it('mounts with current content and creates assistant message', function (): void {
            livewire(AiChatInterface::class, ['currentContent' => 'Initial content'])
                ->assertSet('content', 'Initial content')
                ->assertCount('messages', 1)
                ->assertSet('messages.0.role', 'assistant')
                ->assertSet('messages.0.content', 'Initial content');
        });

        it('mounts with system prompt', function (): void {
            livewire(AiChatInterface::class, ['systemPrompt' => 'You are a helpful assistant.'])
                ->assertSet('systemPrompt', 'You are a helpful assistant.');
        });

        it('mounts with context prompt', function (): void {
            livewire(AiChatInterface::class, ['contextPrompt' => 'The user is editing a product description.'])
                ->assertSet('contextPrompt', 'The user is editing a product description.');
        });

        it('pre-fills user input with initial prompt', function (): void {
            livewire(AiChatInterface::class, ['initialPrompt' => 'Make it more engaging'])
                ->assertSet('userInput', 'Make it more engaging');
        });

        it('uses custom model when provided', function (): void {
            livewire(AiChatInterface::class, ['model' => 'gpt-4o'])
                ->assertSet('model', 'gpt-4o');
        });

        it('uses default model from config when not specified', function (): void {
            config(['filament-ai-forms.model' => 'gpt-4.1-mini']);

            livewire(AiChatInterface::class)
                ->assertSet('model', 'gpt-4.1-mini');
        });
    });

    describe('message deletion', function (): void {
        it('can delete a message by index', function (): void {
            $messages = [
                ['role' => 'user', 'content' => 'Message 1', 'timestamp' => now()->toIso8601String()],
                ['role' => 'assistant', 'content' => 'Response 1', 'timestamp' => now()->toIso8601String()],
                ['role' => 'user', 'content' => 'Message 2', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->call('deleteMessage', 0)
                ->assertCount('messages', 2)
                ->assertSet('messages.0.content', 'Response 1')
                ->assertSet('messages.1.content', 'Message 2');
        });

        it('updates content to latest assistant message after delete', function (): void {
            $messages = [
                ['role' => 'assistant', 'content' => 'First', 'timestamp' => now()->toIso8601String()],
                ['role' => 'user', 'content' => 'More', 'timestamp' => now()->toIso8601String()],
                ['role' => 'assistant', 'content' => 'Second', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->call('deleteMessage', 2)
                ->assertSet('currentGeneratedContent', 'First')
                ->assertSet('content', 'First');
        });

        it('clears content when no assistant messages remain', function (): void {
            $messages = [
                ['role' => 'assistant', 'content' => 'Only one', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->set('currentGeneratedContent', 'Only one')
                ->set('content', 'Only one')
                ->call('deleteMessage', 0)
                ->assertSet('currentGeneratedContent', '')
                ->assertSet('content', '')
                ->assertSet('messages', []);
        });
    });

    describe('property defaults', function (): void {
        it('has default streaming content', function (): void {
            livewire(AiChatInterface::class)
                ->assertSet('streamingContent', '');
        });

        it('has default error message as null', function (): void {
            livewire(AiChatInterface::class)
                ->assertSet('errorMessage', null);
        });

        it('has default generating state as false', function (): void {
            livewire(AiChatInterface::class)
                ->assertSet('generating', false);
        });
    });

    describe('content binding', function (): void {
        it('allows content to be modified', function (): void {
            livewire(AiChatInterface::class, ['currentContent' => 'Initial'])
                ->set('content', 'Modified')
                ->assertSet('content', 'Modified');
        });

        it('tracks current generated content separately', function (): void {
            livewire(AiChatInterface::class, ['currentContent' => 'Original content'])
                ->assertSet('currentGeneratedContent', 'Original content');
        });
    });

    describe('getLatestAssistantMessage', function (): void {
        it('returns last assistant message content', function (): void {
            $messages = [
                ['role' => 'user', 'content' => 'Hi', 'timestamp' => now()->toIso8601String()],
                ['role' => 'assistant', 'content' => 'First response', 'timestamp' => now()->toIso8601String()],
                ['role' => 'user', 'content' => 'More please', 'timestamp' => now()->toIso8601String()],
                ['role' => 'assistant', 'content' => 'Latest response', 'timestamp' => now()->toIso8601String()],
            ];

            $component = livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->instance();

            expect($component->getLatestAssistantMessage())->toBe('Latest response');
        });

        it('returns empty string when no assistant messages', function (): void {
            $messages = [
                ['role' => 'user', 'content' => 'Hi', 'timestamp' => now()->toIso8601String()],
            ];

            $component = livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->instance();

            expect($component->getLatestAssistantMessage())->toBe('');
        });

        it('returns empty string when messages is empty', function (): void {
            $component = livewire(AiChatInterface::class)
                ->set('messages', [])
                ->instance();

            expect($component->getLatestAssistantMessage())->toBe('');
        });
    });

    describe('regenerate behavior', function (): void {
        it('clears all messages on regenerate', function (): void {
            $messages = [
                ['role' => 'user', 'content' => 'Hi', 'timestamp' => now()->toIso8601String()],
                ['role' => 'assistant', 'content' => 'Hello', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->set('currentGeneratedContent', 'Hello')
                ->set('content', 'Hello')
                ->call('regenerate')
                ->assertCount('messages', 1)
                ->assertSet('messages.0.role', 'user')
                ->assertSet('messages.0.content', 'Generate fresh content based on the context.')
                ->assertSet('currentGeneratedContent', '')
                ->assertSet('content', '')
                ->assertSet('errorMessage', null);
        });

        it('uses custom regenerate prompt', function (): void {
            $messages = [
                ['role' => 'assistant', 'content' => 'Old content', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class, ['regeneratePrompt' => 'Start over with new ideas.'])
                ->set('messages', $messages)
                ->call('regenerate')
                ->assertSet('messages.0.content', 'Start over with new ideas.');
        });
    });

    describe('sendMessage', function (): void {
        it('validates userInput is required', function (): void {
            livewire(AiChatInterface::class)
                ->set('userInput', '')
                ->call('sendMessage')
                ->assertHasErrors(['userInput' => 'required']);
        });

        it('validates userInput max length of 10000', function (): void {
            livewire(AiChatInterface::class)
                ->set('userInput', str_repeat('a', 10001))
                ->call('sendMessage')
                ->assertHasErrors(['userInput' => 'max']);
        });

        it('adds user message to messages array', function (): void {
            livewire(AiChatInterface::class)
                ->set('userInput', 'Hello AI')
                ->call('sendMessage')
                ->assertCount('messages', 1)
                ->assertSet('messages.0.role', 'user')
                ->assertSet('messages.0.content', 'Hello AI');
        });

        it('clears userInput after adding message', function (): void {
            livewire(AiChatInterface::class)
                ->set('userInput', 'Test message')
                ->call('sendMessage')
                ->assertSet('userInput', '');
        });

        it('trims whitespace from userInput', function (): void {
            livewire(AiChatInterface::class)
                ->set('userInput', '  Hello  ')
                ->call('sendMessage')
                ->assertSet('messages.0.content', 'Hello');
        });
    });

    describe('performGeneration', function (): void {
        it('sets generating to true', function (): void {
            livewire(AiChatInterface::class)
                ->call('performGeneration')
                ->assertSet('generating', true);
        });

        it('clears errorMessage', function (): void {
            livewire(AiChatInterface::class)
                ->set('errorMessage', 'Previous error')
                ->call('performGeneration')
                ->assertSet('errorMessage', null);
        });

        it('resets streamingContent', function (): void {
            livewire(AiChatInterface::class)
                ->set('streamingContent', 'Previous content')
                ->call('performGeneration')
                ->assertSet('streamingContent', '');
        });
    });

    describe('render', function (): void {
        it('renders successfully', function (): void {
            livewire(AiChatInterface::class)
                ->assertStatus(200);
        });
    });

    describe('startStreaming', function (): void {
        it('adds assistant response to messages', function (): void {
            $this->mockOpenAiStreamSuccess('Generated text');

            $messages = [
                ['role' => 'user', 'content' => 'Test prompt', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertCount('messages', 2)
                ->assertSet('messages.1.role', 'assistant')
                ->assertSet('messages.1.content', 'Generated text');
        });

        it('updates currentGeneratedContent after streaming', function (): void {
            $this->mockOpenAiStreamSuccess('New content');

            $messages = [
                ['role' => 'user', 'content' => 'Generate something', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertSet('currentGeneratedContent', 'New content');
        });

        it('updates content property after streaming', function (): void {
            $this->mockOpenAiStreamSuccess('Updated content');

            $messages = [
                ['role' => 'user', 'content' => 'Update', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertSet('content', 'Updated content');
        });

        it('clears streamingContent after completion', function (): void {
            $this->mockOpenAiStreamSuccess('Final response');

            $messages = [
                ['role' => 'user', 'content' => 'Test', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('streamingContent', 'Previous streaming...')
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertSet('streamingContent', '');
        });

        it('sets generating to false after completion', function (): void {
            $this->mockOpenAiStreamSuccess('Done');

            $messages = [
                ['role' => 'user', 'content' => 'Test', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('generating', true)
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertSet('generating', false);
        });

        it('handles exception by setting errorMessage', function (): void {
            $this->mockOpenAiException('API failed');

            $messages = [
                ['role' => 'user', 'content' => 'This will fail', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertSet('errorMessage', 'Failed to generate response. Please try again.')
                ->assertSet('generating', false)
                ->assertSet('streamingContent', '');
        });

        it('uses custom model from mount', function (): void {
            $this->mockOpenAiStreamSuccess('GPT-4o response');

            $messages = [
                ['role' => 'user', 'content' => 'Test', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class, ['model' => 'gpt-4o'])
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertSet('content', 'GPT-4o response');
        });

        it('dispatches ai-content-generated event after streaming', function (): void {
            $this->mockOpenAiStreamSuccess('Updated content');

            $messages = [
                ['role' => 'user', 'content' => 'Update', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class)
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertDispatched('ai-content-generated');
        });

        it('prepends contextPrompt as first user message in API call', function (): void {
            $this->mockOpenAiStreamSuccess('Response with context');

            $messages = [
                ['role' => 'user', 'content' => 'Tell me more', 'timestamp' => now()->toIso8601String()],
            ];

            livewire(AiChatInterface::class, ['contextPrompt' => 'You are helping edit a product description.'])
                ->set('messages', $messages)
                ->call('startStreaming')
                ->assertSet('content', 'Response with context');

            // Verify contextPrompt was prepended as first user message
            OpenAI::assertSent(Responses::class, function (string $method, array $params): bool {
                if ($method !== 'createStreamed') {
                    return false;
                }

                $input = $params['input'] ?? [];

                // First message should be the contextPrompt
                return isset($input[0])
                    && $input[0]['role'] === 'user'
                    && $input[0]['content'] === 'You are helping edit a product description.';
            });
        });
    });
});
