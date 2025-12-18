<?php

declare(strict_types=1);

/**
 * Isolated wizard integration tests for AiGenerateAction.
 *
 * These tests MUST run in isolation due to Livewire/Filament test state pollution.
 * Running mountAction tests after other livewire tests causes RootTagMissingFromViewException.
 *
 * To run these tests:
 *   vendor/bin/pest --group=wizard-isolation
 *
 * Or run this file directly:
 *   vendor/bin/pest tests/Feature/AiGenerateActionWizardTest.php
 *
 * NOTE: Only ONE test from this file can pass per test run due to state pollution.
 * Use --filter to run specific tests individually.
 *
 * @see https://github.com/filamentphp/filament/issues/17857
 * @see https://github.com/livewire/livewire/discussions/6706
 */

use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;
use Gwhthompson\FilamentAiForms\Data\AiGenerationResult;
use Gwhthompson\FilamentAiForms\Services\AiFormGenerationService;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestEditPage;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestModel;
use Mockery\MockInterface;

use function Pest\Livewire\livewire;

covers(AiGenerateAction::class);

it('halts wizard when no fields selected and shows notification', function (): void {
    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm([
            'field_name' => false,
            'field_description' => false,
        ])
        ->goToNextWizardStep()
        ->assertNotified()
        ->assertActionHalted('aiGenerate');
})->group('wizard-isolation');

it('proceeds to step 2 when fields selected', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.5,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm([
            'field_name' => true,
            'field_description' => false,
        ])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2);
})->group('wizard-isolation');

it('passes selected fields to service', function (): void {
    $capturedFields = null;

    $this->mock(AiFormGenerationService::class, function (MockInterface $mock) use (&$capturedFields): void {
        $mock->shouldReceive('generate')
            ->once()
            ->withArgs(function ($config, $components, $context, $selectedFields) use (&$capturedFields) {
                $capturedFields = $selectedFields;

                return true;
            })
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Test', 'description' => 'Test'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 2,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm([
            'field_name' => true,
            'field_description' => true,
        ])
        ->goToNextWizardStep();

    expect($capturedFields)->toContain('name')
        ->and($capturedFields)->toContain('description');
})->group('wizard-isolation');

it('handles service errors gracefully', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andThrow(new RuntimeException('OpenAI API error'));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm([
            'field_name' => true,
        ])
        ->goToNextWizardStep()
        ->assertNotified()
        ->assertActionHalted('aiGenerate');
})->group('wizard-isolation');

it('handles timeout errors gracefully', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andThrow(new RuntimeException('Connection timeout'));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm([
            'field_name' => true,
        ])
        ->goToNextWizardStep()
        ->assertNotified()
        ->assertActionHalted('aiGenerate');
})->group('wizard-isolation');
