<?php

declare(strict_types=1);

use Filament\Panel;
use Gwhthompson\FilamentAiForms\Actions\AiChatAction;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;
use Gwhthompson\FilamentAiForms\Agents\ChatStreamAgent;
use Gwhthompson\FilamentAiForms\Agents\FormGenerationAgent;
use Gwhthompson\FilamentAiForms\FilamentAiFormsPlugin;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestEditPage;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestModel;

covers(FilamentAiFormsPlugin::class);

describe('FilamentAiFormsPlugin', function (): void {
    describe('instantiation', function (): void {
        it('can be instantiated with make()', function (): void {
            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin)->toBeInstanceOf(FilamentAiFormsPlugin::class);
        });

        it('returns plugin ID', function (): void {
            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getId())->toBe('filament-ai-forms');
        });
    });

    describe('agent configuration', function (): void {
        it('returns null agent by default', function (): void {
            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getAgent())->toBeNull();
        });

        it('can set custom agent class', function (): void {
            $plugin = FilamentAiFormsPlugin::make()
                ->agent(FormGenerationAgent::class);

            expect($plugin->getAgent())->toBe(FormGenerationAgent::class);
        });

        it('returns agent from config when set', function (): void {
            config(['filament-ai-forms.agents.generation' => FormGenerationAgent::class]);

            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getAgent())->toBe(FormGenerationAgent::class);
        });

        it('returns fluent instance when setting agent', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $result = $plugin->agent(FormGenerationAgent::class);

            expect($result)->toBe($plugin);
        });
    });

    describe('chat agent configuration', function (): void {
        it('returns null chat agent by default', function (): void {
            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getChatAgent())->toBeNull();
        });

        it('can set custom chat agent class', function (): void {
            $plugin = FilamentAiFormsPlugin::make()
                ->chatAgent(ChatStreamAgent::class);

            expect($plugin->getChatAgent())->toBe(ChatStreamAgent::class);
        });

        it('returns chat agent from config when set', function (): void {
            config(['filament-ai-forms.agents.chat' => ChatStreamAgent::class]);

            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getChatAgent())->toBe(ChatStreamAgent::class);
        });

        it('returns fluent instance when setting chat agent', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $result = $plugin->chatAgent(ChatStreamAgent::class);

            expect($result)->toBe($plugin);
        });
    });

    describe('fluent configuration', function (): void {
        it('supports chained configuration', function (): void {
            $plugin = FilamentAiFormsPlugin::make()
                ->agent(FormGenerationAgent::class)
                ->chatAgent(ChatStreamAgent::class);

            expect($plugin->getAgent())->toBe(FormGenerationAgent::class)
                ->and($plugin->getChatAgent())->toBe(ChatStreamAgent::class);
        });
    });

    describe('boot method', function (): void {
        it('can be booted with a panel', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $panel = Mockery::mock(Panel::class);

            $plugin->boot($panel);

            expect($plugin)->toBeInstanceOf(FilamentAiFormsPlugin::class);
        });
    });

    describe('static get method', function (): void {
        it('retrieves plugin from filament container', function (): void {
            // Initialize the panel context by mounting a Livewire component
            $record = TestModel::factory()->create();

            \Pest\Livewire\livewire(TestEditPage::class, ['record' => $record->id])
                ->assertStatus(200);

            $plugin = FilamentAiFormsPlugin::get();

            expect($plugin)->toBeInstanceOf(FilamentAiFormsPlugin::class);
        });
    });

    describe('plugin agent config wired into actions', function (): void {
        it('AiGenerateAction resolves agent from plugin when set', function (): void {
            $record = TestModel::factory()->create();

            // Boot a panel so FilamentAiFormsPlugin::get() works
            \Pest\Livewire\livewire(TestEditPage::class, ['record' => $record->id])
                ->assertStatus(200);

            // Configure the plugin with a custom agent
            $plugin = FilamentAiFormsPlugin::get();
            $plugin->agent(FormGenerationAgent::class);

            // Create an action without explicit agent — should resolve from plugin
            $action = AiGenerateAction::make();

            // Use reflection to call the protected method
            $method = new ReflectionMethod($action, 'resolveAgentClass');

            expect($method->invoke($action))->toBe(FormGenerationAgent::class);
        });

        it('AiChatAction resolves agent from plugin when set', function (): void {
            $record = TestModel::factory()->create();

            // Boot a panel so FilamentAiFormsPlugin::get() works
            \Pest\Livewire\livewire(TestEditPage::class, ['record' => $record->id])
                ->assertStatus(200);

            // Configure the plugin with a custom chat agent
            $plugin = FilamentAiFormsPlugin::get();
            $plugin->chatAgent(ChatStreamAgent::class);

            // Create an action without explicit agent — should resolve from plugin
            $action = AiChatAction::make();

            expect($action->getAgentClass())->toBe(ChatStreamAgent::class);
        });
    });
});
