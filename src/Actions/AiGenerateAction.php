<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Gwhthompson\FilamentAiForms\Data\AiFieldMetadata;
use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Exceptions\AiGenerationException;
use Gwhthompson\FilamentAiForms\FilamentAiFormsPlugin;
use Gwhthompson\FilamentAiForms\Services\AiFormGenerationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Override;
use Throwable;

/**
 * Generic Filament action for AI-powered form generation.
 *
 * This action provides a 3-step wizard where users can:
 * 1. Select which fields to generate using AI
 * 2. Watch AI generation progress
 * 3. Review and accept/reject suggestions
 *
 * Usage:
 * ```php
 * AiGenerateAction::make()
 *     ->agent(MyFormAgent::class)
 *     ->systemPrompt('You are a specialist...')
 *     ->contextProvider(fn($action) => ['url' => $action->getRecord()->website])
 * ```
 */
class AiGenerateAction extends Action
{
    protected string|Closure|null $agentProvider = null;

    protected string|Closure|null $systemPrompt = null;

    protected ?bool $logEnabled = null;

    protected ?string $logPath = null;

    protected ?Closure $beforeGeneration = null;

    protected ?Closure $afterGeneration = null;

    protected ?Closure $contextProvider = null;

    /** @var array<int, mixed> */
    protected array $tools = [];

    public static function getDefaultName(): ?string
    {
        return 'aiGenerate';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (): string => $this->isEditPage() ? 'Optimise using AI' : 'Generate using AI')
            ->icon(Heroicon::Sparkles)
            ->color('primary')
            ->visible(fn (): bool => $this->shouldBeVisible())
            ->steps(fn (): array => $this->getWizardSteps())
            ->modalWidth('5xl')
            ->modalHeading(fn (): string => $this->isEditPage() ? 'Optimise Form with AI' : 'Generate Form with AI')
            ->action(function (array $data): mixed {
                /** @var array<string, mixed> $data */
                return $this->handleAction($data);
            });
    }

    /** Configure the Agent class for generation. */
    public function agent(string|Closure $agent): static
    {
        $this->agentProvider = $agent;

        return $this;
    }

    /** Configure system prompt. */
    public function systemPrompt(string|Closure $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    /** Configure logging. */
    public function logEnabled(bool $enabled = true): static
    {
        $this->logEnabled = $enabled;

        return $this;
    }

    /** Configure log path. */
    public function logPath(string $path): static
    {
        $this->logPath = $path;

        return $this;
    }

    /** Hook called before generation starts. */
    public function beforeGeneration(Closure $callback): static
    {
        $this->beforeGeneration = $callback;

        return $this;
    }

    /** Hook called after generation completes. */
    public function afterGeneration(Closure $callback): static
    {
        $this->afterGeneration = $callback;

        return $this;
    }

    /**
     * Hook for providing custom context.
     *
     * Callback receives action instance and should return context array.
     */
    public function contextProvider(Closure $callback): static
    {
        $this->contextProvider = $callback;

        return $this;
    }

    /**
     * Configure tools for the default agent (e.g., WebSearch).
     *
     * @param  array<int, mixed>  $tools
     */
    public function tools(array $tools): static
    {
        $this->tools = $tools;

        return $this;
    }

    /** Resolve the agent class. Action-level > plugin-level > config-level. */
    protected function resolveAgentClass(): ?string
    {
        if ($this->agentProvider !== null) {
            if ($this->agentProvider instanceof Closure) {
                $result = $this->evaluate($this->agentProvider);

                return is_string($result) ? $result : null;
            }

            return $this->agentProvider;
        }

        try {
            $pluginAgent = FilamentAiFormsPlugin::get()->getAgent();
            if ($pluginAgent !== null) {
                return $pluginAgent;
            }
        } catch (\Throwable) {
            // No active panel (standalone use, tests) — fall through to config
        }

        $configValue = config('filament-ai-forms.agents.generation');

        return is_string($configValue) ? $configValue : null;
    }

    /** Resolve the system prompt, evaluating closures via Filament's evaluate(). */
    private function resolveSystemPrompt(): ?string
    {
        if ($this->systemPrompt === null) {
            return null;
        }

        if ($this->systemPrompt instanceof Closure) {
            $result = $this->evaluate($this->systemPrompt);

            return is_string($result) ? $result : null;
        }

        return $this->systemPrompt;
    }

    /** Check if action should be visible. */
    protected function shouldBeVisible(): bool
    {
        return $this->isEditPage();
    }

    /** Check if we're on an Edit page. */
    protected function isEditPage(): bool
    {
        $livewire = $this->getLivewire();

        return $livewire !== null && method_exists($livewire, 'getRecord') && $livewire->getRecord() !== null;
    }

    /**
     * Get the 3-step wizard configuration.
     *
     * @return array<int, Step>
     */
    protected function getWizardSteps(): array
    {
        return [
            // Step 1: Field Selection
            Step::make('select')
                ->key('ai-field-selection-step')
                ->label('Select Fields')
                ->description('Choose which fields to generate using AI')
                ->schema(fn (): array => $this->getFieldSelectionSchema())
                ->afterValidation(function (array $state, callable $set): void {
                    // Count selected fields
                    /** @var array<string, mixed> $state */
                    $selected = collect($state)
                        ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'field_') && $value === true)
                        ->count();

                    if ($selected === 0) {
                        Notification::make()
                            ->warning()
                            ->title('No fields selected')
                            ->body('Please select at least one field to generate.')
                            ->send();

                        $this->halt();
                    }

                    // Perform AI generation
                    $this->performAiGeneration($state, $set);
                }),

            // Step 2: Review & Accept
            Step::make('review')
                ->key('ai-review-step')
                ->label('Review')
                ->description('Review and accept generated content')
                ->schema(fn (callable $get): array => $this->getReviewSchema($get)),
        ];
    }

    /**
     * Get field selection schema for Step 1.
     *
     * @return array<int, mixed>
     */
    protected function getFieldSelectionSchema(): array
    {
        $aiFields = $this->extractFieldMetadata();
        $existingData = $this->getExistingData();

        $schema = [];

        // Select All / Deselect All action
        /** @var view-string $selectButtonsView */
        $selectButtonsView = 'filament-ai-forms::actions.partials.select-all-buttons';
        $schema[] = ViewEntry::make('select_actions')
            ->hiddenLabel()
            ->view($selectButtonsView);

        foreach ($aiFields as $aiField) {
            $fieldName = $aiField->name;
            $currentValue = $existingData[$fieldName] ?? null;
            $hasValue = ! empty($currentValue);

            $description = $aiField->description ?? 'No description available';
            $statusBadge = $hasValue
                ? '<span class="text-success-600 dark:text-success-400">✓ Has value</span>'
                : '<span class="text-gray-500">Empty</span>';

            $schema[] = Checkbox::make("field_{$fieldName}")
                ->label($aiField->label)
                ->helperText(new HtmlString($description.'<br><small>'.$statusBadge.'</small>'))
                ->default(! $hasValue) // Auto-select empty fields
                ->live();
        }

        return $schema;
    }

    /**
     * Perform AI generation and store results in form state.
     *
     * @param  array<string, mixed>  $state
     */
    protected function performAiGeneration(array $state, callable $set): void
    {
        try {
            // Get context (URL, etc.)
            $context = $this->getContext();

            // Execute before hook if defined
            if ($this->beforeGeneration instanceof Closure) {
                ($this->beforeGeneration)($context);
            }

            // Extract selected field names
            /** @var array<int, string> $selectedFields */
            $selectedFields = collect($state)
                ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'field_') && $value === true)
                ->keys()
                ->map(fn (string $key): string => str_replace('field_', '', $key))
                ->values()
                ->all();

            // Get components
            $components = $this->extractComponents();

            $config = AiGenerationConfig::from([
                'agentClass' => $this->resolveAgentClass(),
                'systemPrompt' => $this->resolveSystemPrompt(),
                'logEnabled' => $this->logEnabled ?? (bool) config('filament-ai-forms.logging.enabled', true),
                'logPath' => $this->logPath,
                'tools' => $this->tools,
            ]);

            // Call AI service
            $service = app(AiFormGenerationService::class);

            $result = $service->generate(
                config: $config,
                components: $components,
                context: $context,
                selectedFields: $selectedFields
            );

            // Execute after hook if defined
            if ($this->afterGeneration instanceof Closure) {
                ($this->afterGeneration)($result);
            }

            // Store generated data in form state for review step
            $set('generated_data', json_encode($result->data));
            $set('selected_fields', json_encode($selectedFields));

            // Pre-populate checkbox states so Alpine.js buttons can find them
            foreach ($selectedFields as $fieldName) {
                if (array_key_exists($fieldName, $result->data)) {
                    $set("accept_{$fieldName}", true);
                }
            }

            Log::info('AI generation completed successfully', [
                'field_count' => $result->fieldsGenerated,
                'duration' => $result->duration,
            ]);
        } catch (Throwable $throwable) {
            Log::error('AI generation failed', [
                'error' => $throwable->getMessage(),
                'error_class' => $throwable::class,
            ]);

            $userMessage = $throwable instanceof AiGenerationException
                ? $throwable->getUserMessage()
                : 'An unexpected error occurred: '.$throwable->getMessage();

            Notification::make()
                ->danger()
                ->title('AI Generation Failed')
                ->body($userMessage)
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * Get review schema for Step 3.
     *
     * @return array<int, mixed>
     */
    protected function getReviewSchema(callable $get): array
    {
        $generatedDataJson = $get('generated_data');
        $selectedFieldsJson = $get('selected_fields');

        if (empty($generatedDataJson) || empty($selectedFieldsJson)) {
            return [
                TextEntry::make('no_data')
                    ->hiddenLabel()
                    ->state('No generated data available. Please go back and try again.'),
            ];
        }

        $generatedDataString = is_string($generatedDataJson) ? $generatedDataJson : '';
        $selectedFieldsString = is_string($selectedFieldsJson) ? $selectedFieldsJson : '';

        /** @var array<string, mixed>|null $generatedData */
        $generatedData = json_decode($generatedDataString, true);
        /** @var array<int, string>|null $selectedFields */
        $selectedFields = json_decode($selectedFieldsString, true);
        $existingData = $this->getExistingData();
        $fields = $this->extractFieldMetadata();

        $schema = [];

        // Hidden fields to persist data through wizard steps
        $schema[] = Hidden::make('generated_data')
            ->default($generatedDataJson);

        $schema[] = Hidden::make('selected_fields')
            ->default($selectedFieldsJson);

        // Accept All / Reject All buttons at top
        /** @var view-string $acceptRejectView */
        $acceptRejectView = 'filament-ai-forms::actions.partials.accept-reject-buttons';
        $schema[] = ViewEntry::make('accept_all_action')
            ->hiddenLabel()
            ->view($acceptRejectView);

        if (! is_array($selectedFields) || ! is_array($generatedData)) {
            $schema[] = TextEntry::make('error_state')
                ->hiddenLabel()
                ->state('AI generation data is corrupted. Please close this modal and try again.')
                ->color('danger');

            return $schema;
        }

        foreach ($selectedFields as $selectedField) {
            if (! is_string($selectedField)) {
                continue;
            }

            if (! array_key_exists($selectedField, $generatedData)) {
                continue;
            }

            $fieldMeta = collect($fields)->firstWhere('name', $selectedField);
            $label = $fieldMeta !== null ? $fieldMeta->label : $selectedField;
            $generatedValue = $generatedData[$selectedField];
            $existingValue = $existingData[$selectedField] ?? null;

            // Create diff display
            $schema[] = Section::make((string) $label)
                ->schema([
                    Grid::make(2)
                        ->schema([
                            // Current value
                            TextEntry::make('current_'.$selectedField)
                                ->label('Current')
                                ->state($this->formatValue($existingValue))
                                ->html(),

                            // Generated value
                            TextEntry::make('generated_'.$selectedField)
                                ->label('AI Generated')
                                ->state($this->formatValue($generatedValue))
                                ->html(),
                        ]),

                    // Accept checkbox
                    Checkbox::make('accept_'.$selectedField)
                        ->label('Accept this generated value')
                        ->default(true)
                        ->live(),
                ]);
        }

        return $schema;
    }

    /** Format a value for display in the review interface. */
    protected function formatValue(mixed $value): string
    {
        if (empty($value)) {
            return '<span class="italic text-gray-400 dark:text-gray-600">Empty</span>';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(function (mixed $item): string {
                    $itemString = is_scalar($item) ? (string) $item : '';

                    return '<span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-100">'.htmlspecialchars($itemString).'</span>';
                })
                ->join(' ');
        }

        $valueString = is_scalar($value) ? (string) $value : '';

        return '<div class="whitespace-pre-wrap">'.htmlspecialchars($valueString).'</div>';
    }

    /**
     * Handle the final action - apply accepted changes to parent form.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleAction(array $data): mixed
    {
        $generatedDataJson = $data['generated_data'] ?? null;
        $selectedFieldsJson = $data['selected_fields'] ?? null;

        if (empty($generatedDataJson) || empty($selectedFieldsJson)) {
            Notification::make()
                ->warning()
                ->title('No data to apply')
                ->send();

            return null;
        }

        $generatedDataString = is_string($generatedDataJson) ? $generatedDataJson : '';
        $selectedFieldsString = is_string($selectedFieldsJson) ? $selectedFieldsJson : '';

        $generatedData = json_decode($generatedDataString, true);
        $selectedFields = json_decode($selectedFieldsString, true);

        if (! is_array($generatedData) || ! is_array($selectedFields)) {
            Notification::make()
                ->danger()
                ->title('Data corrupted')
                ->body('AI generation data is corrupted. Please try again.')
                ->send();

            return null;
        }

        $livewire = $this->getLivewire();

        if ($livewire === null) {
            return null;
        }

        // Build array of accepted fields to apply
        /** @var array<string, mixed> $dataToApply */
        $dataToApply = collect($selectedFields)
            ->filter(
                fn (mixed $fieldName): bool => is_string($fieldName)
                    && ($data['accept_'.$fieldName] ?? false) === true
                    && array_key_exists($fieldName, $generatedData)
            )
            ->mapWithKeys(
                fn (mixed $fieldName): array => [(string) $fieldName => $generatedData[(string) $fieldName]]
            )
            ->toArray();

        if (empty($dataToApply)) {
            Notification::make()
                ->info()
                ->title('No changes to apply')
                ->body('Please accept at least one field to update the form.')
                ->send();

            return null;
        }

        // Apply changes using Filament's partial fill API
        if ($livewire instanceof HasSchemas) {
            $livewire->getSchema('form')?->fillPartially($dataToApply, array_keys($dataToApply));
        }

        Notification::make()
            ->success()
            ->title('Changes applied')
            ->body(sprintf(
                'Applied %d AI-generated field%s to the form.',
                count($dataToApply),
                count($dataToApply) === 1 ? '' : 's'
            ))
            ->send();

        return null;
    }

    /**
     * Get context for generation (URL, domain, custom data).
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function getContext(): array
    {
        // Use custom context provider if defined
        if ($this->contextProvider instanceof Closure) {
            $result = ($this->contextProvider)($this);

            if (is_array($result)) {
                /** @var array<string, mixed> $result */
                return $result;
            }

            /** @var array<string, mixed> */
            return [];
        }

        /** @var array<string, mixed> */
        return [];
    }

    /**
     * Get existing data from the form (current state, not database state).
     *
     * @return array<string, mixed>
     */
    protected function getExistingData(): array
    {
        $livewire = $this->getLivewire();

        if ($livewire === null) {
            return [];
        }

        /** @var array<string, mixed> */
        return $livewire->data ?? [];
    }

    /**
     * Extract AI-enabled components from parent form.
     *
     * @return array<int, Component>
     */
    protected function extractComponents(): array
    {
        $livewire = $this->getLivewire();

        if ($livewire === null || ! $livewire instanceof HasSchemas) {
            return [];
        }

        $form = $livewire->getSchema('form');
        assert($form !== null, 'EditRecord always has a form schema');

        /** @var array<int, Component> */
        return collect($form->getFlatComponents())
            ->filter(function (mixed $component): bool {
                return $component instanceof Component && $component->isAiEnabled();
            })
            ->values()
            ->all();
    }

    /**
     * Extract field metadata for UI display purposes.
     *
     * @return array<int, AiFieldMetadata>
     */
    protected function extractFieldMetadata(): array
    {
        $components = $this->extractComponents();

        return collect($components)
            ->map(
                function (Component $component): AiFieldMetadata {
                    $schema = $component->getAiSchema();
                    $description = is_array($schema) ? ($schema['description'] ?? null) : null;
                    $prompt = is_array($schema) ? ($schema['prompt'] ?? null) : null;
                    $examples = is_array($schema) ? ($schema['examples'] ?? []) : [];

                    return AiFieldMetadata::from([
                        'name' => $component->getName(),
                        'label' => $component->getLabel() ?? $component->getName(),
                        'description' => is_string($description) ? $description : null,
                        'prompt' => is_string($prompt) ? $prompt : null,
                        'examples' => is_array($examples) ? $examples : [],
                    ]);
                }
            )
            ->values()
            ->all();
    }
}
