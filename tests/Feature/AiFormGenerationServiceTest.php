<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Gwhthompson\FilamentAiForms\Agents\FormGenerationAgent;
use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Services\AiFormGenerationService;
use Gwhthompson\FilamentAiForms\Tests\Traits\MocksAgent;

uses(MocksAgent::class);

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

        $this->mockFormGenerationSuccess([
            'name' => 'Acme Corporation',
            'description' => 'A leading technology company.',
        ]);

        $config = AiGenerationConfig::from([]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
            context: ['url' => 'https://example.com'],
        );

        expect($result->data)->toBeArray()
            ->and($result->data['name'])->toBe('Acme Corporation')
            ->and($result->data['description'])->toBe('A leading technology company.')
            ->and($result->fieldsGenerated)->toBe(2);

        FormGenerationAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'example.com'));
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

        $this->mockFormGenerationSuccess([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $config = AiGenerationConfig::from([]);

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

        $this->mockFormGenerationSuccess([
            'status' => 'active',
        ]);

        $config = AiGenerationConfig::from([]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
        );

        expect($result->data['status'])->toBe('active');
    });

    it('throws exception when agent fails', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true),
        ];

        $this->mockFormGenerationException('AI service unavailable');

        $config = AiGenerationConfig::from([]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(RuntimeException::class, 'AI service unavailable');
    });

    it('wraps timeout exceptions', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true),
        ];

        $this->mockFormGenerationException('Connection timeout occurred');

        $config = AiGenerationConfig::from([]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(\Gwhthompson\FilamentAiForms\Exceptions\AiServiceTimeoutException::class);
    });

    it('uses fallback prompt when no URL in context', function (): void {
        $components = [
            TextInput::make('name')
                ->aiSchema(
                    enabled: true,
                    description: 'A person name',
                ),
        ];

        $this->mockFormGenerationSuccess([
            'name' => 'Generated Name',
        ]);

        $config = AiGenerationConfig::from([]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
            context: [],
        );

        expect($result->data['name'])->toBe('Generated Name')
            ->and($result->userPrompt)->toBe('Please generate the following data:');
    });

    it('includes context in generation', function (): void {
        $components = [
            TextInput::make('name')
                ->aiSchema(
                    enabled: true,
                    description: 'Extract business name from website',
                ),
        ];

        $this->mockFormGenerationSuccess([
            'name' => 'Example Company',
        ]);

        $config = AiGenerationConfig::from([]);

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

describe('mergeWithExistingData', function (): void {
    it('replaces existing data in replace mode', function (): void {
        $aiData = ['name' => 'New Name', 'email' => 'new@example.com'];
        $existing = ['name' => 'Old Name', 'phone' => '123456'];

        $result = $this->service->mergeWithExistingData($aiData, $existing, 'replace');

        expect($result)->toBe([
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    });

    it('merges data keeping non-empty existing values in merge mode', function (): void {
        $aiData = ['name' => 'AI Name', 'email' => 'ai@example.com', 'bio' => 'AI Bio'];
        $existing = ['name' => 'Existing Name', 'email' => '', 'phone' => '123456'];

        $result = $this->service->mergeWithExistingData($aiData, $existing, 'merge');

        expect($result['name'])->toBe('Existing Name')
            ->and($result['email'])->toBe('ai@example.com')
            ->and($result['bio'])->toBe('AI Bio');
    });

    it('defaults to replace for invalid merge mode', function (): void {
        $aiData = ['name' => 'AI Name', 'email' => 'ai@example.com'];
        $existing = ['name' => 'Existing Name', 'phone' => '123456'];

        $result = $this->service->mergeWithExistingData($aiData, $existing, 'invalid_mode');

        expect($result)->toBe($aiData);
    });

    it('only fills empty fields in enhance mode', function (): void {
        $aiData = ['name' => 'AI Name', 'email' => 'ai@example.com'];
        $existing = ['name' => 'Existing Name', 'email' => ''];

        $result = $this->service->mergeWithExistingData($aiData, $existing, 'enhance');

        expect($result['name'])->toBe('Existing Name')
            ->and($result['email'])->toBe('ai@example.com');
    });
});
