<?php

declare(strict_types=1);

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Services\AiFormGenerationService;
use Gwhthompson\FilamentAiForms\Tests\Traits\MocksOpenAi;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Responses;

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

    it('throws exception on incomplete response with unknown reason when details missing', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true),
        ];

        $this->mockOpenAiIncompleteNoDetails();

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(RuntimeException::class, 'OpenAI response incomplete: unknown');
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

    it('throws exception on non-array JSON response', function (): void {
        // Disable retries completely for this test
        config()->set('filament-ai-forms.retry.max_attempts', 0);

        $components = [
            TextInput::make('name')->aiSchema(enabled: true),
        ];

        $this->mockOpenAiNonArrayJson('"just a plain string"');

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(RuntimeException::class, 'OpenAI returned invalid JSON');
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

describe('AiFormGenerationService response validation', function (): void {
    it('throws exception when OpenAI returns non-array JSON', function (): void {
        // OpenAI Structured Outputs should always return valid JSON objects,
        // but if it returns a scalar JSON value, we throw immediately
        $components = [
            TextInput::make('name')
                ->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiNonArrayJson('"just a string"');

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        expect(fn () => $this->service->generate(
            config: $config,
            components: $components,
        ))->toThrow(RuntimeException::class, 'OpenAI returned invalid JSON');
    });

    it('returns valid response with multiple fields', function (): void {
        $components = [
            TextInput::make('name')
                ->aiSchema(enabled: true, description: 'Name'),
            Textarea::make('description')
                ->aiSchema(enabled: true, description: 'Description'),
        ];

        $this->mockOpenAiSuccess([
            'name' => 'Test Company',
            'description' => 'A valid description.',
        ]);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $result = $this->service->generate(
            config: $config,
            components: $components,
        );

        expect($result->data['name'])->toBe('Test Company')
            ->and($result->data['description'])->toBe('A valid description.');
    });
});

describe('AiFormGenerationService optional config params', function (): void {
    it('includes topP in request when set', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiSuccess(['name' => 'Generated']);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
            'topP' => 0.9,
        ]);

        $this->service->generate(config: $config, components: $components);

        OpenAI::assertSent(Responses::class, fn (string $method, array $params): bool => $method === 'create' && isset($params['top_p']) && $params['top_p'] === 0.9
        );
    });

    it('includes maxOutputTokens in request when set', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiSuccess(['name' => 'Generated']);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
            'maxOutputTokens' => 2000,
        ]);

        $this->service->generate(config: $config, components: $components);

        OpenAI::assertSent(Responses::class, fn (string $method, array $params): bool => $method === 'create' && isset($params['max_output_tokens']) && $params['max_output_tokens'] === 2000
        );
    });

    it('includes store in request when set', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiSuccess(['name' => 'Generated']);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
            'store' => true,
        ]);

        $this->service->generate(config: $config, components: $components);

        OpenAI::assertSent(Responses::class, fn (string $method, array $params): bool => $method === 'create' && isset($params['store']) && $params['store'] === true
        );
    });

    it('includes include array in request when set', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiSuccess(['name' => 'Generated']);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
            'include' => ['usage', 'reasoning'],
        ]);

        $this->service->generate(config: $config, components: $components);

        OpenAI::assertSent(Responses::class, fn (string $method, array $params): bool => $method === 'create' && isset($params['include']) && $params['include'] === ['usage', 'reasoning']
        );
    });

    it('excludes optional params when not set', function (): void {
        $components = [
            TextInput::make('name')->aiSchema(enabled: true, description: 'Name'),
        ];

        $this->mockOpenAiSuccess(['name' => 'Generated']);

        $config = AiGenerationConfig::from([
            'model' => 'gpt-4.1-mini',
        ]);

        $this->service->generate(config: $config, components: $components);

        OpenAI::assertSent(Responses::class, fn (string $method, array $params): bool => $method === 'create'
            && ! isset($params['top_p'])
            && ! isset($params['max_output_tokens'])
            && ! isset($params['store'])
            && ! isset($params['include'])
        );
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
