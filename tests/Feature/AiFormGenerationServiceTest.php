<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Services\AiFormGenerationService;
use Gwhthompson\FilamentAiForms\Tests\Traits\MocksOpenAi;

uses(MocksOpenAi::class);

beforeEach(function (): void {
    $this->service = app(AiFormGenerationService::class);
});

describe('AiFormGenerationService', function (): void {
    it('generates form data from components with AI schema', function (): void {
        $components = [
            TextInput::make('name')
                ->aiSchema(
                    enabled: true,
                    description: 'Company name',
                    required: true,
                ),
            Textarea::make('description')
                ->aiSchema(
                    enabled: true,
                    description: 'Company description',
                ),
        ];

        $this->mockOpenAiSuccess([
            'name' => 'Acme Corporation',
            'description' => 'A leading technology company.',
        ]);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
            'temperature' => 0.1,
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
            context: ['url' => 'https://example.com'],
        );

        expect($result->data)->toBeArray()
            ->and($result->data['name'])->toBe('Acme Corporation')
            ->and($result->data['description'])->toBe('A leading technology company.')
            ->and($result->fieldsGenerated)->toBe(2);
    });

    it('generates data for selected fields only', function (): void {
        $components = [
            TextInput::make('name')
                ->aiSchema(enabled: true, description: 'Name'),
            TextInput::make('email')
                ->aiSchema(enabled: true, description: 'Email'),
            TextInput::make('phone')
                ->aiSchema(enabled: true, description: 'Phone'),
        ];

        $this->mockOpenAiSuccess([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
            context: [],
            selectedFields: ['name', 'email'],
        );

        expect($result->data)->toHaveKeys(['name', 'email'])
            ->and($result->fieldsGenerated)->toBe(2);
    });

    it('handles enum fields correctly', function (): void {
        $components = [
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'pending' => 'Pending',
                ])
                ->aiSchema(
                    enabled: true,
                    description: 'Account status',
                ),
        ];

        $this->mockOpenAiSuccess([
            'status' => 'active',
        ]);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
        );

        expect($result->data['status'])->toBe('active');
    });

    it('throws exception on incomplete response', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true),
        ];

        $this->mockOpenAiIncomplete('max_output_tokens');

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(RuntimeException::class, 'OpenAI response incomplete');
    });

    it('throws exception on null content', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true),
        ];

        $this->mockOpenAiNullContent();

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(RuntimeException::class, 'OpenAI returned no content');
    });

    it('throws exception on empty content', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true),
        ];

        $this->mockOpenAiEmptyContent();

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(RuntimeException::class, 'OpenAI returned no content');
    });

    it('includes context in generation', function (): void {
        $components = [
            TextInput::make('name')
                ->aiSchema(
                    enabled: true,
                    description: 'Extract business name from website',
                ),
        ];

        $this->mockOpenAiSuccess([
            'name' => 'Example Company',
        ]);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
            context: [
                'url' => 'https://example.com',
                'domain' => 'example.com',
            ],
        );

        expect($result->data['name'])->toBe('Example Company');
    });
});

describe('AiFormGenerationService retry logic', function (): void {
    it('skips validation when disabled', function (): void {
        // Disable validation
        config()->set('filament-ai-forms.retry.validate_schema', false);

        $components = [
            TextInput::make('name')
                ->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiSuccess(['name' => 'test']);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
        );

        // Should accept without running validation
        expect($result->data['name'])->toBe('test');
    });

    it('passes through valid response when validation enabled', function (): void {
        // Enable validation
        config()->set('filament-ai-forms.retry.validate_schema', true);
        config()->set('filament-ai-forms.retry.max_attempts', 2);

        $components = [
            TextInput::make('name')
                ->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiSuccess(['name' => 'Valid Name']);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
        );

        // Should return without retry when no violations
        expect($result->data['name'])->toBe('Valid Name');
    });

    it('returns first valid response without retrying', function (): void {
        // Enable validation with retries
        config()->set('filament-ai-forms.retry.validate_schema', true);
        config()->set('filament-ai-forms.retry.max_attempts', 3);

        $components = [
            TextInput::make('name')
                ->aiSchema(enabled: true, description: 'Name'),
            Textarea::make('description')
                ->aiSchema(enabled: true, description: 'Description'),
        ];

        $this->mockOpenAiSuccess([
            'name' => 'Test Company',
            'description' => 'A valid description that passes validation.',
        ]);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
        );

        expect($result->data['name'])->toBe('Test Company')
            ->and($result->data['description'])->toBe('A valid description that passes validation.');
    });
});

describe('mergeWithExistingData', function (): void {
    it('replaces existing data in replace mode', function (): void {
        $service = app(AiFormGenerationService::class);

        $aiData = ['name' => 'New Name', 'email' => 'new@example.com'];
        $existing = ['name' => 'Old Name', 'phone' => '123456'];

        $result = $service->mergeWithExistingData($aiData, $existing, 'replace');

        expect($result)->toBe([
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    });

    it('merges data keeping non-empty existing values in merge mode', function (): void {
        $service = app(AiFormGenerationService::class);

        $aiData = ['name' => 'AI Name', 'email' => 'ai@example.com', 'bio' => 'AI Bio'];
        $existing = ['name' => 'Existing Name', 'email' => '', 'phone' => '123456'];

        $result = $service->mergeWithExistingData($aiData, $existing, 'merge');

        expect($result['name'])->toBe('Existing Name')
            ->and($result['email'])->toBe('ai@example.com')
            ->and($result['bio'])->toBe('AI Bio');
    });

    it('only fills empty fields in enhance mode', function (): void {
        $service = app(AiFormGenerationService::class);

        $aiData = ['name' => 'AI Name', 'email' => 'ai@example.com'];
        $existing = ['name' => 'Existing Name', 'email' => ''];

        $result = $service->mergeWithExistingData($aiData, $existing, 'enhance');

        expect($result['name'])->toBe('Existing Name')
            ->and($result['email'])->toBe('ai@example.com');
    });
});
