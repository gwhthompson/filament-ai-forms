<?php

declare(strict_types=1);

/**
 * Wizard integration tests for AiGenerateAction.
 *
 * These tests exercise the wizard modal flow including field selection,
 * AI generation, and error handling.
 *
 * @see ResetsFilamentState
 */

use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;
use Gwhthompson\FilamentAiForms\Data\AiGenerationResult;
use Gwhthompson\FilamentAiForms\Exceptions\AiResponseParseException;
use Gwhthompson\FilamentAiForms\Exceptions\AiServiceTimeoutException;
use Gwhthompson\FilamentAiForms\Services\AiFormGenerationService;
use Gwhthompson\FilamentAiForms\Tests\Concerns\ResetsFilamentState;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestEditPage;
use Gwhthompson\FilamentAiForms\Tests\Fixtures\TestEditPageWithHooks;
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
});

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
});

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
});

it('handles service errors gracefully', function (string $exceptionClass, string $message): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock) use ($exceptionClass, $message): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andThrow(new $exceptionClass($message));
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
})->with([
    'general error' => [RuntimeException::class, 'AI API error'],
    'timeout error' => [AiServiceTimeoutException::class, 'Connection timeout'],
    'parse error' => [AiResponseParseException::class, 'Invalid JSON response'],
    'unexpected error' => [RuntimeException::class, 'Database connection failed'],
]);

it('completes full wizard and applies accepted changes to form', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => [
                    'name' => 'AI Generated Company',
                    'description' => 'AI Generated Description',
                ],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 2,
                'duration' => 0.5,
            ]));
    });

    $record = TestModel::factory()->create([
        'name' => 'Original Name',
        'description' => 'Original Description',
    ]);

    $testable = livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        // Step 1: Select fields
        ->fillForm([
            'field_name' => true,
            'field_description' => true,
        ])
        ->goToNextWizardStep()
        // Step 2: Review - accept both fields (default is true)
        ->assertWizardCurrentStep(2)
        ->fillForm([
            'accept_name' => true,
            'accept_description' => true,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified('Changes applied');

    // Verify the form data was updated on the same component
    expect($testable->get('data.name'))->toBe('AI Generated Company')
        ->and($testable->get('data.description'))->toBe('AI Generated Description');
});

it('applies only accepted fields when some are rejected', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => [
                    'name' => 'AI Generated Name',
                    'description' => 'AI Generated Description',
                ],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 2,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create([
        'name' => 'Original Name',
        'description' => 'Original Description',
    ]);

    $testable = livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm([
            'field_name' => true,
            'field_description' => true,
        ])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        // Accept name but reject description
        ->fillForm([
            'accept_name' => true,
            'accept_description' => false,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified('Changes applied');

    // Verify only accepted field was updated on the same component
    expect($testable->get('data.name'))->toBe('AI Generated Name')
        ->and($testable->get('data.description'))->toBe('Original Description');
});

it('shows notification when no fields accepted', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.2,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        // Reject all fields
        ->fillForm(['accept_name' => false])
        ->callMountedAction()
        ->assertNotified('No changes to apply');
});

it('executes beforeGeneration hook during generation', function (): void {
    $beforeCalled = false;
    $receivedContext = null;

    // Bind both hooks before creating the Livewire component
    app()->singleton('test.beforeGeneration', function () use (&$beforeCalled, &$receivedContext) {
        return function (array $context) use (&$beforeCalled, &$receivedContext): void {
            $beforeCalled = true;
            $receivedContext = $context;
        };
    });

    app()->singleton('test.afterGeneration', function () {
        return function ($result): void {
            // No-op for this test
        };
    });

    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPageWithHooks::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2);

    expect($beforeCalled)->toBeTrue()
        ->and($receivedContext)->toBeArray();
});

it('shows empty badge for fields without values', function (): void {
    // Create record with empty description to test the "Empty" badge path
    $record = TestModel::factory()->create([
        'name' => 'Test Company',
        'description' => '',  // Empty field triggers line 274
    ]);

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->assertSee('Empty')  // Verify the empty badge is shown
        ->assertSee('Has value');  // Name field has value
});

it('executes afterGeneration hook with result', function (): void {
    $afterCalled = false;
    $receivedResult = null;

    // Bind both hooks before creating the Livewire component
    app()->singleton('test.beforeGeneration', function () {
        return function ($context): void {
            // No-op for this test
        };
    });

    app()->singleton('test.afterGeneration', function () use (&$afterCalled, &$receivedResult) {
        return function ($result) use (&$afterCalled, &$receivedResult): void {
            $afterCalled = true;
            $receivedResult = $result;
        };
    });

    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Hook Test Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPageWithHooks::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2);

    expect($afterCalled)->toBeTrue()
        ->and($receivedResult)->toBeInstanceOf(AiGenerationResult::class)
        ->and($receivedResult->data['name'])->toBe('Hook Test Name');
});

it('skips fields not present in generated data', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Only Name'],  // Missing 'description' intentionally
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create([
        'name' => 'Original Name',
        'description' => 'Original Description',
    ]);

    $testable = livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm([
            'field_name' => true,
            'field_description' => true,  // Selected but not in generated result
        ])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        ->fillForm(['accept_name' => true])  // Only accept_name exists since description wasn't generated
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified('Changes applied');

    // Only name should be updated, description should remain original
    expect($testable->get('data.name'))->toBe('Only Name')
        ->and($testable->get('data.description'))->toBe('Original Description');
});

it('handles corrupted generated_data JSON in handleAction', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        // Corrupt the generated_data by setting it to invalid JSON
        ->set('mountedActions.0.data.generated_data', 'not valid json')
        ->callMountedAction()
        ->assertNotified('Data corrupted');
});

it('handles corrupted selected_fields JSON in handleAction', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        // Corrupt the selected_fields by setting it to invalid JSON
        ->set('mountedActions.0.data.selected_fields', 'not valid json')
        ->callMountedAction()
        ->assertNotified('Data corrupted');
});

it('shows error in review schema when generated data is corrupted', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        // Corrupt the data and verify error message appears in schema
        ->set('mountedActions.0.data.generated_data', '"just a string"')  // Valid JSON but not an array
        ->assertSee('corrupted');
});

it('handles non-string field names in selected_fields gracefully', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    // Inject selected_fields with non-string values (triggers line 442 continue)
    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        ->set('mountedActions.0.data.selected_fields', '["name", 123, null]')
        ->fillForm(['accept_name' => true])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified('Changes applied');
});

it('shows warning when generated_data and selected_fields are empty', function (): void {
    $this->mock(AiFormGenerationService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(AiGenerationResult::from([
                'data' => ['name' => 'Generated Name'],
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
                'duration' => 0.3,
            ]));
    });

    $record = TestModel::factory()->create();

    // Set both fields to empty to trigger lines 516-521
    livewire(TestEditPage::class, ['record' => $record->id])
        ->mountAction('aiGenerate')
        ->fillForm(['field_name' => true])
        ->goToNextWizardStep()
        ->assertWizardCurrentStep(2)
        ->set('mountedActions.0.data.generated_data', '')
        ->set('mountedActions.0.data.selected_fields', '')
        ->callMountedAction()
        ->assertNotified('No data to apply');
});
