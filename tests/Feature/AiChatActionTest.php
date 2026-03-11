<?php

declare(strict_types=1);

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Gwhthompson\FilamentAiForms\Actions\AiChatAction;
use Gwhthompson\FilamentAiForms\Agents\ChatStreamAgent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component as LivewireComponent;

covers(AiChatAction::class);

describe('AiChatAction', function (): void {
    describe('configuration', function (): void {
        it('can be instantiated with default name', function (): void {
            $action = AiChatAction::make();

            expect($action->getName())->toBe('refineWithAi');
        });

        it('can configure system prompt with string', function (): void {
            $prompt = 'You are a professional copywriter.';
            $action = AiChatAction::make()
                ->systemPrompt($prompt);

            expect($action->getSystemPrompt())->toBe($prompt);
        });

        it('can configure system prompt with closure', function (): void {
            $action = AiChatAction::make()
                ->systemPrompt(fn () => 'Dynamic system prompt');

            expect($action->getSystemPrompt())->toBe('Dynamic system prompt');
        });

        it('can configure initial prompt with string', function (): void {
            $prompt = 'Help me improve this description.';
            $action = AiChatAction::make()
                ->initialPrompt($prompt);

            expect($action->getInitialPrompt())->toBe($prompt);
        });

        it('can configure initial prompt with closure', function (): void {
            $action = AiChatAction::make()
                ->initialPrompt(fn () => 'Dynamic initial prompt');

            expect($action->getInitialPrompt())->toBe('Dynamic initial prompt');
        });

        it('can configure context prompt with string', function (): void {
            $prompt = 'The user is editing a product listing.';
            $action = AiChatAction::make()
                ->contextPrompt($prompt);

            expect($action->getContextPrompt())->toBe($prompt);
        });

        it('can configure context prompt with closure', function (): void {
            $action = AiChatAction::make()
                ->contextPrompt(fn () => 'Dynamic context prompt');

            expect($action->getContextPrompt())->toBe('Dynamic context prompt');
        });
    });

    describe('default values', function (): void {
        it('has default label', function (): void {
            $action = AiChatAction::make();

            expect($action->getLabel())->toBe('Refine with AI');
        });

        it('has default system prompt', function (): void {
            $action = AiChatAction::make();

            expect($action->getSystemPrompt())->toBe('You are a helpful AI assistant.');
        });

        it('has empty initial prompt by default', function (): void {
            $action = AiChatAction::make();

            expect($action->getInitialPrompt())->toBe('');
        });

        it('has empty context prompt by default', function (): void {
            $action = AiChatAction::make();

            expect($action->getContextPrompt())->toBe('');
        });
    });

    describe('fluent configuration', function (): void {
        it('supports chained configuration', function (): void {
            $action = AiChatAction::make()
                ->systemPrompt('System prompt')
                ->initialPrompt('Initial prompt')
                ->contextPrompt('Context prompt');

            expect($action->getSystemPrompt())->toBe('System prompt')
                ->and($action->getInitialPrompt())->toBe('Initial prompt')
                ->and($action->getContextPrompt())->toBe('Context prompt');
        });

        it('returns self when setting system prompt', function (): void {
            $action = AiChatAction::make();
            $result = $action->systemPrompt('Test');

            expect($result)->toBe($action);
        });

        it('returns self when setting initial prompt', function (): void {
            $action = AiChatAction::make();
            $result = $action->initialPrompt('Test');

            expect($result)->toBe($action);
        });

        it('returns self when setting context prompt', function (): void {
            $action = AiChatAction::make();
            $result = $action->contextPrompt('Test');

            expect($result)->toBe($action);
        });
    });

    describe('mountUsing closure', function (): void {
        it('stores current field content in livewire aiChatContent property', function (): void {
            $action = AiChatAction::make();

            // Create a mock Livewire component
            $livewire = new class extends LivewireComponent
            {
                public ?string $aiChatContent = null;

                public function render()
                {
                    return '';
                }
            };

            $component = Mockery::mock(Component::class);

            // Get the mountUsing closure via reflection
            $reflection = new ReflectionClass($action);
            $property = $reflection->getProperty('mountUsing');
            $property->setAccessible(true);
            $mountUsing = $property->getValue($action);

            // Invoke the closure
            $mountUsing($livewire, $component, 'Test content');

            expect($livewire->aiChatContent)->toBe('Test content');
        });

        it('handles null state by storing empty string', function (): void {
            $action = AiChatAction::make();

            $livewire = new class extends LivewireComponent
            {
                public ?string $aiChatContent = null;

                public function render()
                {
                    return '';
                }
            };

            $component = Mockery::mock(Component::class);

            $reflection = new ReflectionClass($action);
            $property = $reflection->getProperty('mountUsing');
            $property->setAccessible(true);
            $mountUsing = $property->getValue($action);

            $mountUsing($livewire, $component, null);

            expect($livewire->aiChatContent)->toBe('');
        });
    });

    describe('action closure', function (): void {
        it('applies content to form field when content is not empty', function (): void {
            $action = AiChatAction::make();

            $livewire = new class extends LivewireComponent
            {
                public string $aiChatContent = 'Applied content';

                public function render()
                {
                    return '';
                }
            };

            $component = Mockery::mock(Component::class);
            $component->shouldReceive('getStatePath')->andReturn('data.description');

            $appliedValue = null;
            $set = Mockery::mock(Set::class);
            $set->shouldReceive('__invoke')
                ->with($component, 'Applied content')
                ->once()
                ->andReturnUsing(function ($comp, $value) use (&$appliedValue) {
                    $appliedValue = $value;
                });

            // Get the action closure via reflection
            $reflection = new ReflectionClass($action);
            $property = $reflection->getProperty('action');
            $property->setAccessible(true);
            $actionClosure = $property->getValue($action);

            $actionClosure($set, $component, $livewire);

            expect($appliedValue)->toBe('Applied content');
        });

        it('does not apply content when content is empty', function (): void {
            $action = AiChatAction::make();

            $livewire = new class extends LivewireComponent
            {
                public string $aiChatContent = '';

                public function render()
                {
                    return '';
                }
            };

            $component = Mockery::mock(Component::class);
            $component->shouldReceive('getStatePath')->andReturn('data.description');

            $set = Mockery::mock(Set::class);
            // Set should NOT be called when content is empty
            $set->shouldNotReceive('__invoke');

            $reflection = new ReflectionClass($action);
            $property = $reflection->getProperty('action');
            $property->setAccessible(true);
            $actionClosure = $property->getValue($action);

            $actionClosure($set, $component, $livewire);
        });

        it('handles non-string aiChatContent by converting to empty string', function (): void {
            $action = AiChatAction::make();

            // Test with null aiChatContent
            $livewire = new class extends LivewireComponent
            {
                public $aiChatContent = null;

                public function render()
                {
                    return '';
                }
            };

            $component = Mockery::mock(Component::class);
            $component->shouldReceive('getStatePath')->andReturn('data.description');

            $set = Mockery::mock(Set::class);
            // Set should NOT be called when content resolves to empty
            $set->shouldNotReceive('__invoke');

            $reflection = new ReflectionClass($action);
            $property = $reflection->getProperty('action');
            $property->setAccessible(true);
            $actionClosure = $property->getValue($action);

            $actionClosure($set, $component, $livewire);
        });
    });

    describe('modalContent closure', function (): void {
        it('returns a view with correct data', function (): void {
            $action = AiChatAction::make()
                ->systemPrompt('Custom system prompt')
                ->initialPrompt('Custom initial prompt')
                ->contextPrompt('Custom context prompt');

            $livewire = new class extends LivewireComponent
            {
                public string $aiChatContent = 'Current field content';

                public function render()
                {
                    return '';
                }
            };

            $component = Mockery::mock(Component::class);
            $component->shouldReceive('getStatePath')->andReturn('data.description');

            $model = Mockery::mock(Model::class);
            $model->shouldReceive('getKey')->andReturn(123);

            $reflection = new ReflectionClass($action);
            $property = $reflection->getProperty('modalContent');
            $property->setAccessible(true);
            $modalContent = $property->getValue($action);

            $view = $modalContent($livewire, $component, $model);

            expect($view)->toBeInstanceOf(View::class)
                ->and($view->getName())->toBe('filament-ai-forms::actions.ai-chat-modal')
                ->and($view->getData()['currentContent'])->toBe('Current field content')
                ->and($view->getData()['systemPrompt'])->toBe('Custom system prompt')
                ->and($view->getData()['initialPrompt'])->toBe('Custom initial prompt')
                ->and($view->getData()['contextPrompt'])->toBe('Custom context prompt')
                ->and($view->getData()['agentClass'])->toBe(ChatStreamAgent::class)
                ->and($view->getData()['identifier'])->toBe('data.description.123');
        });
    });
});
