<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;
use Gwhthompson\FilamentAiForms\Agents\FormGenerationAgent;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestEditPage;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestModel;

use function Pest\Livewire\livewire;

covers(AiGenerateAction::class);

describe('AiGenerateAction on edit page', function (): void {
    it('exists on edit page', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate');
    });

    it('is visible on edit page', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionVisible('aiGenerate');
    });

    it('is enabled on edit page', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionEnabled('aiGenerate');
    });

    it('has sparkles icon', function (): void {
        $action = AiGenerateAction::make();

        expect($action->getIcon())->toBe(Heroicon::Sparkles);
    });

    it('has primary color', function (): void {
        $action = AiGenerateAction::make();

        expect($action->getColor())->toBe('primary');
    });
});

// Test action configuration via assertActionExists callback pattern
describe('AiGenerateAction configuration via livewire callback', function (): void {
    it('has sparkles icon via livewire', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate', fn (Action $action): bool => $action->getIcon() === Heroicon::Sparkles
            );
    });

    it('has primary color via livewire', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate', fn (Action $action): bool => $action->getColor() === 'primary'
            );
    });

    it('has 5xl modal width', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate', fn (Action $action): bool => $action->getModalWidth() === '5xl'
            );
    });

    it('has correct modal heading on edit page', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate', fn (Action $action): bool => $action->getModalHeading() === 'Optimise Form with AI'
            );
    });

    it('has correct label on edit page', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate', fn (Action $action): bool => $action->getLabel() === 'Optimise using AI'
            );
    });

    it('is configured as wizard action', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate', fn (Action $action): bool => $action->isWizard()
            );
    });

    it('has empty modal footer actions for wizard', function (): void {
        $record = TestModel::factory()->create();

        livewire(TestEditPage::class, ['record' => $record->id])
            ->assertActionExists('aiGenerate', fn (Action $action): bool => $action->getModalFooterActions() === []
            );
    });
});

describe('AiGenerateAction page data', function (): void {
    it('loads existing data from record', function (): void {
        $record = TestModel::factory()->create([
            'name' => 'Existing Company',
            'description' => 'Existing Description',
        ]);

        $testable = livewire(TestEditPage::class, ['record' => $record->id]);

        expect($testable->get('data.name'))->toBe('Existing Company')
            ->and($testable->get('data.description'))->toBe('Existing Description');
    });
});

describe('AiGenerateAction configuration', function (): void {
    it('can be instantiated with default name', function (): void {
        $action = AiGenerateAction::make();

        expect($action->getName())->toBe('aiGenerate');
    });

    it('supports fluent configuration', function (): void {
        $action = AiGenerateAction::make()
            ->agent(FormGenerationAgent::class)
            ->systemPrompt('Test prompt')
            ->logEnabled(true)
            ->logPath('/tmp/logs');

        expect($action)->toBeInstanceOf(AiGenerateAction::class);
    });

    it('supports closure for systemPrompt', function (): void {
        $action = AiGenerateAction::make()
            ->systemPrompt(fn (): string => 'Dynamic prompt');

        expect($action)->toBeInstanceOf(AiGenerateAction::class);
    });

});

describe('AiGenerateAction context', function (): void {
    it('returns empty array when no context provider', function (): void {
        $action = AiGenerateAction::make();

        expect($action->getContext())->toBe([]);
    });

    it('invokes context provider and returns result', function (): void {
        $action = AiGenerateAction::make()
            ->contextProvider(fn () => ['url' => 'https://example.com', 'key' => 'value']);

        expect($action->getContext())
            ->toBe(['url' => 'https://example.com', 'key' => 'value']);
    });

    it('returns empty array when context provider returns non-array', function (): void {
        $action = AiGenerateAction::make()
            ->contextProvider(fn () => 'invalid');

        expect($action->getContext())->toBe([]);
    });

    it('passes action instance to context provider', function (): void {
        $receivedAction = null;
        $action = AiGenerateAction::make()
            ->contextProvider(function ($passedAction) use (&$receivedAction) {
                $receivedAction = $passedAction;

                return [];
            });

        $action->getContext();

        expect($receivedAction)->toBe($action);
    });
});

describe('AiGenerateAction hooks', function (): void {
    it('can configure beforeGeneration hook', function (): void {
        $hookConfigured = false;

        $action = AiGenerateAction::make()
            ->beforeGeneration(function () use (&$hookConfigured): void {
                $hookConfigured = true;
            });

        expect($action)->toBeInstanceOf(AiGenerateAction::class);
    });

    it('can configure afterGeneration hook', function (): void {
        $hookConfigured = false;

        $action = AiGenerateAction::make()
            ->afterGeneration(function () use (&$hookConfigured): void {
                $hookConfigured = true;
            });

        expect($action)->toBeInstanceOf(AiGenerateAction::class);
    });

    it('can configure context provider', function (): void {
        $action = AiGenerateAction::make()
            ->contextProvider(fn () => ['url' => 'https://example.com']);

        expect($action->getContext())->toBe(['url' => 'https://example.com']);
    });
});
