<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Agents\ChatStreamAgent;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;

covers(ChatStreamAgent::class);

describe('ChatStreamAgent', function (): void {
    describe('instructions()', function (): void {
        it('returns the default instructions', function (): void {
            $agent = new ChatStreamAgent;

            expect($agent->instructions())->toBe('You are a helpful AI assistant.');
        });

        it('returns custom instructions', function (): void {
            $agent = new ChatStreamAgent(
                systemInstructions: 'You are a creative writing assistant.',
            );

            expect($agent->instructions())->toBe('You are a creative writing assistant.');
        });
    });

    describe('messages()', function (): void {
        it('returns the conversation history passed to constructor', function (): void {
            $history = [
                new Message(MessageRole::User, 'Hello'),
                new Message(MessageRole::Assistant, 'Hi there!'),
                new Message(MessageRole::User, 'How are you?'),
            ];

            $agent = new ChatStreamAgent(conversationHistory: $history);

            $messages = $agent->messages();

            expect($messages)->toHaveCount(3)
                ->and($messages[0]->role)->toBe(MessageRole::User)
                ->and($messages[0]->content)->toBe('Hello')
                ->and($messages[1]->role)->toBe(MessageRole::Assistant)
                ->and($messages[1]->content)->toBe('Hi there!')
                ->and($messages[2]->role)->toBe(MessageRole::User)
                ->and($messages[2]->content)->toBe('How are you?');
        });

        it('returns empty array when no history provided', function (): void {
            $agent = new ChatStreamAgent;

            expect($agent->messages())->toBe([]);
        });
    });
});
