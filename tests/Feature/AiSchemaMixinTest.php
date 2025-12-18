<?php

declare(strict_types=1);

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Gwhthompson\FilamentAiForms\Mixins\AiSchemaMixin;

covers(AiSchemaMixin::class);

describe('AiSchemaMixin', function (): void {
    describe('aiSchema() method', function (): void {
        it('enables AI by default when aiSchema is called', function (): void {
            $component = TextInput::make('name')->aiSchema();

            expect($component->isAiEnabled())->toBeTrue();
        });

        it('can explicitly disable AI', function (): void {
            $component = TextInput::make('name')->aiSchema(enabled: false);

            expect($component->isAiEnabled())->toBeFalse();
        });

        it('stores description', function (): void {
            $component = TextInput::make('name')->aiSchema(
                description: 'The company name'
            );

            expect($component->getAiDescription())->toBe('The company name');
        });

        it('stores prompt', function (): void {
            $component = TextInput::make('name')->aiSchema(
                prompt: 'Extract the official business name'
            );

            expect($component->getAiPrompt())->toBe('Extract the official business name');
        });

        it('stores examples', function (): void {
            $component = TextInput::make('brand')->aiSchema(
                examples: ['Nike', 'Adidas', 'Puma']
            );

            expect($component->getAiExamples())->toBe(['Nike', 'Adidas', 'Puma']);
        });

        it('stores pattern', function (): void {
            $component = TextInput::make('phone')->aiSchema(
                pattern: '^\d{3}-\d{3}-\d{4}$'
            );

            expect($component->getAiPattern())->toBe('^\d{3}-\d{3}-\d{4}$');
        });

        it('has required true by default', function (): void {
            $component = TextInput::make('name')->aiSchema();

            expect($component->getAiRequired())->toBeTrue();
        });

        it('can set required to false', function (): void {
            $component = TextInput::make('nickname')->aiSchema(required: false);

            expect($component->getAiRequired())->toBeFalse();
        });
    });

    describe('getAiSchema() method', function (): void {
        it('returns full schema array', function (): void {
            $component = TextInput::make('name')->aiSchema(
                enabled: true,
                description: 'Test description',
                prompt: 'Test prompt',
                required: true,
                examples: ['Example 1'],
                pattern: '^[A-Z]'
            );

            $schema = $component->getAiSchema();

            expect($schema)->toBeArray()
                ->and($schema['enabled'])->toBeTrue()
                ->and($schema['description'])->toBe('Test description')
                ->and($schema['prompt'])->toBe('Test prompt')
                ->and($schema['required'])->toBeTrue()
                ->and($schema['examples'])->toBe(['Example 1'])
                ->and($schema['pattern'])->toBe('^[A-Z]');
        });

        it('returns null when aiSchema not configured', function (): void {
            $component = TextInput::make('name');

            expect($component->getAiSchema())->toBeNull();
        });
    });

    describe('isAiEnabled() method', function (): void {
        it('returns false when aiSchema not configured', function (): void {
            $component = TextInput::make('name');

            expect($component->isAiEnabled())->toBeFalse();
        });

        it('returns true when explicitly enabled', function (): void {
            $component = TextInput::make('name')->aiSchema(enabled: true);

            expect($component->isAiEnabled())->toBeTrue();
        });
    });

    describe('getAiDescription() method', function (): void {
        it('returns null when not configured', function (): void {
            $component = TextInput::make('name');

            expect($component->getAiDescription())->toBeNull();
        });

        it('returns null when aiSchema configured without description', function (): void {
            $component = TextInput::make('name')->aiSchema();

            expect($component->getAiDescription())->toBeNull();
        });
    });

    describe('getAiPrompt() method', function (): void {
        it('returns null when not configured', function (): void {
            $component = TextInput::make('name');

            expect($component->getAiPrompt())->toBeNull();
        });

        it('returns null when aiSchema configured without prompt', function (): void {
            $component = TextInput::make('name')->aiSchema();

            expect($component->getAiPrompt())->toBeNull();
        });
    });

    describe('getAiRequired() method', function (): void {
        it('returns true when not configured (default)', function (): void {
            $component = TextInput::make('name');

            expect($component->getAiRequired())->toBeTrue();
        });
    });

    describe('getAiExamples() method', function (): void {
        it('returns empty array when not configured', function (): void {
            $component = TextInput::make('name');

            expect($component->getAiExamples())->toBe([]);
        });

        it('returns empty array when aiSchema configured without examples', function (): void {
            $component = TextInput::make('name')->aiSchema();

            expect($component->getAiExamples())->toBe([]);
        });
    });

    describe('getAiPattern() method', function (): void {
        it('returns null when not configured', function (): void {
            $component = TextInput::make('name');

            expect($component->getAiPattern())->toBeNull();
        });

        it('returns null when aiSchema configured without pattern', function (): void {
            $component = TextInput::make('name')->aiSchema();

            expect($component->getAiPattern())->toBeNull();
        });
    });

    describe('getEffectiveAiPrompt() method', function (): void {
        it('returns null when not configured', function (): void {
            $component = TextInput::make('name');

            expect($component->getEffectiveAiPrompt())->toBeNull();
        });

        it('returns prompt when both prompt and description are set', function (): void {
            $component = TextInput::make('name')->aiSchema(
                description: 'Description',
                prompt: 'Prompt'
            );

            expect($component->getEffectiveAiPrompt())->toBe('Prompt');
        });

        it('falls back to description when prompt is null', function (): void {
            $component = TextInput::make('name')->aiSchema(
                description: 'Description'
            );

            expect($component->getEffectiveAiPrompt())->toBe('Description');
        });

        it('returns null when both prompt and description are null', function (): void {
            $component = TextInput::make('name')->aiSchema();

            expect($component->getEffectiveAiPrompt())->toBeNull();
        });
    });

    describe('works with different component types', function (): void {
        it('works with Textarea', function (): void {
            $component = Textarea::make('bio')->aiSchema(
                description: 'Biography text'
            );

            expect($component->isAiEnabled())->toBeTrue()
                ->and($component->getAiDescription())->toBe('Biography text');
        });

        it('works with Select', function (): void {
            $component = Select::make('status')
                ->options(['draft' => 'Draft', 'published' => 'Published'])
                ->aiSchema(description: 'Publication status');

            expect($component->isAiEnabled())->toBeTrue()
                ->and($component->getAiDescription())->toBe('Publication status');
        });

        it('works with Checkbox', function (): void {
            $component = Checkbox::make('is_active')->aiSchema(
                description: 'Whether active'
            );

            expect($component->isAiEnabled())->toBeTrue()
                ->and($component->getAiDescription())->toBe('Whether active');
        });
    });
});
