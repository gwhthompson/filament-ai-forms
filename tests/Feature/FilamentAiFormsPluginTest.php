<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\FilamentAiFormsPlugin;

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

    describe('model configuration', function (): void {
        it('returns default model from config', function (): void {
            config(['filament-ai-forms.model' => 'gpt-4.1-mini']);

            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getModel())->toBe('gpt-4.1-mini');
        });

        it('can set custom model', function (): void {
            $plugin = FilamentAiFormsPlugin::make()
                ->model('gpt-4o');

            expect($plugin->getModel())->toBe('gpt-4o');
        });

        it('returns fluent instance when setting model', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $result = $plugin->model('gpt-4o');

            expect($result)->toBe($plugin);
        });
    });

    describe('temperature configuration', function (): void {
        it('returns default temperature from config', function (): void {
            config(['filament-ai-forms.temperature' => 0.05]);

            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getTemperature())->toBe(0.05);
        });

        it('can set custom temperature', function (): void {
            $plugin = FilamentAiFormsPlugin::make()
                ->temperature(0.7);

            expect($plugin->getTemperature())->toBe(0.7);
        });

        it('returns fluent instance when setting temperature', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $result = $plugin->temperature(0.5);

            expect($result)->toBe($plugin);
        });
    });

    describe('web search configuration', function (): void {
        it('has web search enabled by default', function (): void {
            config(['filament-ai-forms.web_search.enabled' => true]);

            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->isWebSearchEnabled())->toBeTrue();
        });

        it('can disable web search', function (): void {
            config(['filament-ai-forms.web_search.enabled' => true]);

            $plugin = FilamentAiFormsPlugin::make()
                ->webSearch(false);

            expect($plugin->isWebSearchEnabled())->toBeFalse();
        });

        it('respects config when web search is disabled', function (): void {
            config(['filament-ai-forms.web_search.enabled' => false]);

            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->isWebSearchEnabled())->toBeFalse();
        });

        it('returns fluent instance when setting web search', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $result = $plugin->webSearch(true);

            expect($result)->toBe($plugin);
        });
    });

    describe('web search country configuration', function (): void {
        it('returns default country from config', function (): void {
            config(['filament-ai-forms.web_search.country' => 'GB']);

            $plugin = FilamentAiFormsPlugin::make();

            expect($plugin->getWebSearchCountry())->toBe('GB');
        });

        it('can set custom country', function (): void {
            $plugin = FilamentAiFormsPlugin::make()
                ->webSearchCountry('US');

            expect($plugin->getWebSearchCountry())->toBe('US');
        });

        it('returns fluent instance when setting country', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $result = $plugin->webSearchCountry('DE');

            expect($result)->toBe($plugin);
        });
    });

    describe('fluent configuration', function (): void {
        it('supports chained configuration', function (): void {
            // Ensure config is enabled for this test
            config(['filament-ai-forms.web_search.enabled' => true]);

            $plugin = FilamentAiFormsPlugin::make()
                ->model('gpt-4o')
                ->temperature(0.3)
                ->webSearch(true)
                ->webSearchCountry('US');

            expect($plugin->getModel())->toBe('gpt-4o')
                ->and($plugin->getTemperature())->toBe(0.3)
                ->and($plugin->isWebSearchEnabled())->toBeTrue()
                ->and($plugin->getWebSearchCountry())->toBe('US');
        });
    });

    describe('boot method', function (): void {
        it('can be booted with a panel', function (): void {
            $plugin = FilamentAiFormsPlugin::make();
            $panel = Mockery::mock(\Filament\Panel::class);

            // Should complete without throwing
            $plugin->boot($panel);

            expect(true)->toBeTrue();
        });
    });

    describe('static get method', function (): void {
        it('retrieves plugin from filament container', function (): void {
            // Initialize the panel context by mounting a Livewire component
            $record = \Gwhthompson\FilamentAiForms\Tests\Fixtures\TestModel::factory()->create();

            \Pest\Livewire\livewire(\Gwhthompson\FilamentAiForms\Tests\Fixtures\TestEditPage::class, ['record' => $record->id])
                ->assertStatus(200);

            $plugin = FilamentAiFormsPlugin::get();

            expect($plugin)->toBeInstanceOf(FilamentAiFormsPlugin::class);
        });
    });
});
