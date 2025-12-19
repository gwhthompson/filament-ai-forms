<?php

declare(strict_types=1);

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Gwhthompson\FilamentAiForms\Services\FilamentToAiSchemaMapper;
use Livewire\Component;
use Livewire\Livewire;

covers(FilamentToAiSchemaMapper::class);

/**
 * Helper to get form components with proper container context.
 *
 * @return array<int, \Filament\Schemas\Components\Component>
 */
function getFormComponents(): array
{
    // Create a minimal anonymous Livewire component that implements HasForms
    $livewire = new class extends Component implements HasForms
    {
        use InteractsWithForms;

        public ?array $data = [];

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    TextInput::make('name')
                        ->required()
                        ->aiSchema(enabled: true, description: 'The name'),
                    TextInput::make('email')
                        ->email()
                        ->aiSchema(enabled: true),
                    TextInput::make('count')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(100)
                        ->aiSchema(enabled: true),
                    TextInput::make('optional_note')
                        ->nullable()
                        ->aiSchema(enabled: true, required: false),
                    Checkbox::make('is_active')
                        ->aiSchema(enabled: true),
                    Select::make('optional_status')
                        ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                        ->nullable()
                        ->aiSchema(enabled: true, required: false),
                ])
                ->statePath('data');
        }

        public function render(): string
        {
            return '<div></div>';
        }
    };

    // Register and mount the component to initialize forms
    Livewire::component('test-form', $livewire::class);

    // Build form with the component as container
    $schema = $livewire->form(Schema::make($livewire));

    return iterator_to_array($schema->getFlatComponents());
}

describe('Form Component Integration', function (): void {
    it('builds schema from mounted form with container context', function (): void {
        $components = getFormComponents();

        $mapper = app(FilamentToAiSchemaMapper::class);
        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties'])
            ->toHaveKey('name')
            ->toHaveKey('email')
            ->toHaveKey('count');
    });

    it('detects integer type with validation rules', function (): void {
        $components = getFormComponents();

        $mapper = app(FilamentToAiSchemaMapper::class);
        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['count']['type'])->toBe('integer');
    });

    it('applies min/max constraints from validation rules', function (): void {
        $components = getFormComponents();

        $mapper = app(FilamentToAiSchemaMapper::class);
        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['count'])
            ->toHaveKey('minimum', 1)
            ->toHaveKey('maximum', 100);
    });

    it('makes field nullable when aiSchema required is false', function (): void {
        $components = getFormComponents();

        $mapper = app(FilamentToAiSchemaMapper::class);
        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['optional_note']['type'])
            ->toBe(['string', 'null']);
    });

    it('detects email format from validation', function (): void {
        $components = getFormComponents();

        $mapper = app(FilamentToAiSchemaMapper::class);
        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['email']['format'])->toBe('email');
    });

    it('detects boolean type for checkbox', function (): void {
        $components = getFormComponents();

        $mapper = app(FilamentToAiSchemaMapper::class);
        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['is_active']['type'])->toBe('boolean');
    });

    it('makes select nullable when nullable validation is set', function (): void {
        $components = getFormComponents();

        $mapper = app(FilamentToAiSchemaMapper::class);
        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['optional_status']['type'])
            ->toBe(['string', 'null']);
    });
});
