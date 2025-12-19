<?php

declare(strict_types=1);

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Gwhthompson\FilamentAiForms\Services\FilamentToAiSchemaMapper;

covers(FilamentToAiSchemaMapper::class);

beforeEach(function (): void {
    $this->mapper = app(FilamentToAiSchemaMapper::class);
});

describe('FilamentToAiSchemaMapper', function (): void {
    describe('buildOpenAiConfig', function (): void {
        it('builds schema from text input components', function (): void {
            $components = [
                TextInput::make('name')
                    ->aiSchema(
                        enabled: true,
                        description: 'The person name',
                        required: true,
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config)->toHaveKeys(['schema', 'systemPrompt', 'userPrompt'])
                ->and($config['schema'])->toBeArray()
                ->and($config['schema']['properties'])->toHaveKey('name')
                ->and($config['schema']['properties']['name']['type'])->toBe('string')
                ->and($config['schema']['properties']['name']['description'])->toBe('The person name');
        });

        it('builds schema with string constraints', function (): void {
            $components = [
                TextInput::make('email')
                    ->email()
                    ->aiSchema(
                        enabled: true,
                        description: 'Email address',
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['schema']['properties']['email']['format'])->toBe('email');
        });

        it('builds schema with url format', function (): void {
            $components = [
                TextInput::make('website')
                    ->url()
                    ->aiSchema(
                        enabled: true,
                        description: 'Website URL',
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['schema']['properties']['website']['format'])->toBe('uri');
        });

        it('builds schema for select with enum options', function (): void {
            $components = [
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->aiSchema(
                        enabled: true,
                        description: 'Publication status',
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            // The mapper uses array_values() which returns the labels
            expect($config['schema']['properties']['status']['enum'])
                ->toBe(['Draft', 'Published', 'Archived']);
        });

        it('builds schema for checkbox as boolean', function (): void {
            $components = [
                Checkbox::make('is_active')
                    ->aiSchema(
                        enabled: true,
                        description: 'Whether the item is active',
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['schema']['properties']['is_active']['type'])->toBe('boolean');
        });

        it('preserves description from aiSchema', function (): void {
            $components = [
                TextInput::make('brand')
                    ->aiSchema(
                        enabled: true,
                        description: 'Brand name',
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['schema']['properties']['brand']['description'])
                ->toBe('Brand name');
        });

        it('filters to selected fields only', function (): void {
            $components = [
                TextInput::make('name')->aiSchema(enabled: true),
                TextInput::make('email')->aiSchema(enabled: true),
                TextInput::make('phone')->aiSchema(enabled: true),
            ];

            $config = $this->mapper->buildOpenAiConfig(
                $components,
                selectedFields: ['name', 'email']
            );

            expect($config['schema']['properties'])->toHaveKeys(['name', 'email'])
                ->and($config['schema']['properties'])->not->toHaveKey('phone');
        });

        it('excludes disabled AI fields', function (): void {
            $components = [
                TextInput::make('name')->aiSchema(enabled: true),
                TextInput::make('internal_id')->aiSchema(enabled: false),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['schema']['properties'])->toHaveKey('name')
                ->and($config['schema']['properties'])->not->toHaveKey('internal_id');
        });

        it('builds required array from required AI fields', function (): void {
            $components = [
                TextInput::make('name')->aiSchema(enabled: true, required: true),
                TextInput::make('nickname')->aiSchema(enabled: true, required: false),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['schema']['required'])->toContain('name')
                ->and($config['schema']['required'])->not->toContain('nickname');
        });

        it('handles textarea component', function (): void {
            $components = [
                Textarea::make('bio')
                    ->aiSchema(
                        enabled: true,
                        description: 'Biography text',
                        prompt: 'Write a professional bio',
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['schema']['properties']['bio']['type'])->toBe('string');
        });

        it('builds default system prompt with generation instructions', function (): void {
            $components = [
                TextInput::make('tagline')
                    ->aiSchema(
                        enabled: true,
                        description: 'Marketing tagline',
                    ),
            ];

            $config = $this->mapper->buildOpenAiConfig($components);

            expect($config['systemPrompt'])->toContain('Generate structured data')
                ->and($config['systemPrompt'])->toContain('proper capitalization');
        });

        it('adds base prompt to system prompt', function (): void {
            $components = [
                TextInput::make('name')->aiSchema(enabled: true),
            ];

            $basePrompt = 'You are analyzing business data.';
            $config = $this->mapper->buildOpenAiConfig($components, $basePrompt);

            expect($config['systemPrompt'])->toContain($basePrompt);
        });
    });
});

describe('validation constraints mapping', function (): void {
    it('maps email format to schema', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('email')
                ->email()
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['email']['format'])->toBe('email');
    });

    it('maps url format to schema', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('website')
                ->url()
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['website']['format'])->toBe('uri');
    });
});

describe('aiSchema required flag behavior', function (): void {
    it('string field is NOT nullable when aiSchema required is true', function (): void {
        $components = [
            TextInput::make('name')
                ->aiSchema(enabled: true, required: true),
        ];

        $config = $this->mapper->buildOpenAiConfig($components);

        // With aiSchema(required: true), type stays as string, not ['string', 'null']
        expect($config['schema']['properties']['name']['type'])->toBe('string');
    });

    it('boolean field is NOT nullable when aiSchema required is true', function (): void {
        $components = [
            Checkbox::make('is_active')
                ->aiSchema(enabled: true, required: true),
        ];

        $config = $this->mapper->buildOpenAiConfig($components);

        // aiSchema(required: true) means never nullable
        expect($config['schema']['properties']['is_active']['type'])->toBe('boolean');
    });

    it('includes field in required array when aiSchema required is true', function (): void {
        $components = [
            TextInput::make('required_field')
                ->aiSchema(enabled: true, required: true),
            TextInput::make('optional_field')
                ->aiSchema(enabled: true, required: false),
        ];

        $config = $this->mapper->buildOpenAiConfig($components);

        expect($config['schema']['required'])
            ->toContain('required_field')
            ->not->toContain('optional_field');
    });
});

describe('aiSchema examples and pattern', function (): void {
    it('stores examples in aiSchema', function (): void {
        $component = TextInput::make('brand')
            ->aiSchema(enabled: true, examples: ['Nike', 'Adidas', 'Puma']);

        expect($component->getAiExamples())->toBe(['Nike', 'Adidas', 'Puma']);
    });

    it('stores pattern in aiSchema', function (): void {
        $component = TextInput::make('phone')
            ->aiSchema(enabled: true, pattern: '^\d{3}-\d{3}-\d{4}$');

        expect($component->getAiPattern())->toBe('^\d{3}-\d{3}-\d{4}$');
    });

    it('includes pattern in schema output', function (): void {
        $components = [
            TextInput::make('sku')
                ->aiSchema(enabled: true, pattern: '^[A-Z]{3}-\d{4}$'),
        ];

        $config = $this->mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['sku']['pattern'])
            ->toBe('^[A-Z]{3}-\d{4}$');
    });
});

describe('edge cases and error handling', function (): void {
    it('throws exception when no AI-generatable components found', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        // Empty array
        expect(fn () => $mapper->buildOpenAiConfig([]))
            ->toThrow(InvalidArgumentException::class, 'No AI-generatable components found in schema');
    });

    it('throws exception when all components have AI disabled', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('name')->aiSchema(enabled: false),
            TextInput::make('email')->aiSchema(enabled: false),
        ];

        expect(fn () => $mapper->buildOpenAiConfig($components))
            ->toThrow(InvalidArgumentException::class, 'No AI-generatable components found in schema');
    });

    it('throws exception for select with no options', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            Select::make('empty_select')
                ->options([])
                ->aiSchema(enabled: true),
        ];

        expect(fn () => $mapper->buildOpenAiConfig($components))
            ->toThrow(InvalidArgumentException::class, 'has no options for enum schema');
    });

    it('throws exception for checkbox list with no options', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            \Filament\Forms\Components\CheckboxList::make('empty_tags')
                ->options([])
                ->aiSchema(enabled: true),
        ];

        expect(fn () => $mapper->buildOpenAiConfig($components))
            ->toThrow(InvalidArgumentException::class, 'has no options for enum array schema');
    });

    it('handles select with callable options', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            Select::make('dynamic_options')
                ->options(fn () => ['a' => 'Option A', 'b' => 'Option B'])
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['dynamic_options']['enum'])
            ->toBe(['Option A', 'Option B']);
    });
});

describe('nullable types', function (): void {
    // Note: The fluent ->nullable() method requires form container context.
    // For isolated unit tests, use ->rules(['nullable']) instead.

    it('makes string not nullable when aiSchema required is true', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('required_name')
                ->aiSchema(enabled: true, required: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        // Type stays as string (not array with null)
        expect($config['schema']['properties']['required_name']['type'])
            ->toBe('string');
    });

    it('makes select not nullable when aiSchema required is true', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            Select::make('required_status')
                ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                ->aiSchema(enabled: true, required: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        // Type stays as string (not array with null)
        expect($config['schema']['properties']['required_status']['type'])
            ->toBe('string');
    });

    it('makes checkbox list nullable when nullable rule is present', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            \Filament\Forms\Components\CheckboxList::make('optional_tags')
                ->options(['php' => 'PHP', 'js' => 'JavaScript'])
                ->rules(['nullable'])
                ->aiSchema(enabled: true, required: false),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['optional_tags']['type'])
            ->toBe(['array', 'null']);
    });

    it('makes checkbox nullable when nullable rule is present', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            Checkbox::make('optional_flag')
                ->rules(['nullable'])
                ->aiSchema(enabled: true, required: false),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['optional_flag']['type'])
            ->toBe(['boolean', 'null']);
    });

    it('makes numeric field nullable when nullable rule is present', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('optional_quantity')
                ->numeric()
                ->rules(['nullable'])
                ->aiSchema(enabled: true, required: false),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['optional_quantity']['type'])
            ->toBe(['number', 'null']);
    });
});

describe('numeric schema constraints', function (): void {
    it('applies min constraint to numeric field', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('quantity')
                ->numeric()
                ->rules(['min:1'])
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['quantity']['minimum'])->toBe(1);
    });

    it('applies max constraint to numeric field', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('rating')
                ->numeric()
                ->rules(['max:10'])
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['rating']['maximum'])->toBe(10);
    });
});

describe('parseValidationRules', function (): void {
    it('parses string validation rules format', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            TextInput::make('email')
                ->rules('required|email|max:255')
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        // The email format should be detected
        expect($config['schema']['properties']['email']['format'])->toBe('email');
    });
});

describe('array constraints', function (): void {
    it('applies minItems constraint to array field', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            \Filament\Forms\Components\CheckboxList::make('tags')
                ->options(['php' => 'PHP', 'js' => 'JavaScript', 'python' => 'Python'])
                ->rules(['min:1'])
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['tags']['minItems'])->toBe(1);
    });

    it('applies maxItems constraint to array field', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            \Filament\Forms\Components\CheckboxList::make('categories')
                ->options(['a' => 'Category A', 'b' => 'Category B', 'c' => 'Category C'])
                ->rules(['max:3'])
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['categories']['maxItems'])->toBe(3);
    });

    it('applies both minItems and maxItems to array field', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            \Filament\Forms\Components\CheckboxList::make('skills')
                ->options(['php' => 'PHP', 'js' => 'JavaScript', 'python' => 'Python', 'go' => 'Go'])
                ->rules(['min:2', 'max:4'])
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['skills']['minItems'])->toBe(2)
            ->and($config['schema']['properties']['skills']['maxItems'])->toBe(4);
    });
});

describe('nullable type arrays', function (): void {
    it('builds array schema with enum items', function (): void {
        $mapper = app(FilamentToAiSchemaMapper::class);

        $components = [
            \Filament\Forms\Components\CheckboxList::make('interests')
                ->options(['sports' => 'Sports', 'music' => 'Music', 'tech' => 'Technology'])
                ->aiSchema(enabled: true),
        ];

        $config = $mapper->buildOpenAiConfig($components);

        expect($config['schema']['properties']['interests']['type'])->toBe('array')
            ->and($config['schema']['properties']['interests']['items']['type'])->toBe('string')
            ->and($config['schema']['properties']['interests']['items']['enum'])->toBe(['Sports', 'Music', 'Technology']);
    });
});
