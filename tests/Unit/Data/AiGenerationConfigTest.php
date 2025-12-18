<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Enums\ReasoningEffort;
use Gwhthompson\FilamentAiForms\Enums\ServiceTier;
use Gwhthompson\FilamentAiForms\Enums\Verbosity;

describe('AiGenerationConfig', function (): void {
    describe('construction', function (): void {
        it('creates with defaults', function (): void {
            $config = new AiGenerationConfig;

            expect($config->model)->toBe('gpt-4.1-mini')
                ->and($config->temperature)->toBeNull()
                ->and($config->topP)->toBeNull()
                ->and($config->maxOutputTokens)->toBeNull()
                ->and($config->serviceTier)->toBeNull()
                ->and($config->store)->toBeNull()
                ->and($config->stream)->toBeFalse()
                ->and($config->include)->toBe([])
                ->and($config->verbosity)->toBeNull()
                ->and($config->reasoningEffort)->toBeNull()
                ->and($config->useWebSearch)->toBeFalse()
                ->and($config->logEnabled)->toBeTrue();
        });

        it('creates with custom model', function (): void {
            $config = new AiGenerationConfig(model: 'gpt-4.1');

            expect($config->model)->toBe('gpt-4.1');
        });

        it('creates with temperature', function (): void {
            $config = new AiGenerationConfig(temperature: 0.7);

            expect($config->temperature)->toBe(0.7);
        });

        it('creates with service tier', function (): void {
            $config = new AiGenerationConfig(serviceTier: ServiceTier::Priority);

            expect($config->serviceTier)->toBe(ServiceTier::Priority);
        });
    });

    describe('computed properties', function (): void {
        it('computes text from verbosity', function (): void {
            $config = new AiGenerationConfig(verbosity: Verbosity::Low);

            expect($config->text)->toBe(['verbosity' => 'low']);
        });

        it('computes text from jsonSchema', function (): void {
            $schema = [
                'type' => 'json_schema',
                'name' => 'test',
                'schema' => ['type' => 'object'],
            ];
            $config = new AiGenerationConfig(jsonSchema: $schema);

            expect($config->text)->toBe(['format' => $schema]);
        });

        it('prefers jsonSchema over verbosity for text', function (): void {
            $schema = [
                'type' => 'json_schema',
                'name' => 'test',
                'schema' => ['type' => 'object'],
            ];
            $config = new AiGenerationConfig(
                verbosity: Verbosity::High,
                jsonSchema: $schema,
            );

            expect($config->text)->toBe(['format' => $schema]);
        });

        it('computes reasoning from reasoningEffort', function (): void {
            $config = new AiGenerationConfig(reasoningEffort: ReasoningEffort::High);

            expect($config->reasoning)->toBe(['effort' => 'high']);
        });

        it('sets reasoning to null when reasoningEffort not set', function (): void {
            $config = new AiGenerationConfig;

            expect($config->reasoning)->toBeNull();
        });
    });

    describe('toRequestParams()', function (): void {
        it('builds params with input', function (): void {
            $config = new AiGenerationConfig(model: 'gpt-4.1-mini');
            $input = [['role' => 'user', 'content' => 'Hello']];

            $params = $config->toRequestParams($input);

            expect($params['model'])->toBe('gpt-4.1-mini')
                ->and($params['input'])->toBe($input);
        });

        it('excludes null values', function (): void {
            $config = new AiGenerationConfig;
            $params = $config->toRequestParams([]);

            // These should NOT be present because they're null
            expect($params)->not->toHaveKey('temperature')
                ->and($params)->not->toHaveKey('top_p')
                ->and($params)->not->toHaveKey('max_output_tokens')
                ->and($params)->not->toHaveKey('service_tier')
                ->and($params)->not->toHaveKey('store')
                ->and($params)->not->toHaveKey('text')
                ->and($params)->not->toHaveKey('reasoning');
        });

        it('excludes empty arrays', function (): void {
            $config = new AiGenerationConfig;
            $params = $config->toRequestParams([]);

            expect($params)->not->toHaveKey('include');
        });

        it('excludes false values', function (): void {
            $config = new AiGenerationConfig(stream: false);
            $params = $config->toRequestParams([]);

            expect($params)->not->toHaveKey('stream');
        });

        it('includes tools when useWebSearch is true', function (): void {
            // Set config for web search
            config()->set('filament-ai-forms.web_search.country', 'US');
            config()->set('filament-ai-forms.web_search.context_size', 'large');

            $config = new AiGenerationConfig(useWebSearch: true);
            $params = $config->toRequestParams([]);

            expect($params)->toHaveKey('tools')
                ->and($params['tools'][0]['type'])->toBe('web_search')
                ->and($params['tools'][0]['user_location']['country'])->toBe('US')
                ->and($params['tools'][0]['search_context_size'])->toBe('large');
        });

        it('uses snake_case for output keys', function (): void {
            $config = new AiGenerationConfig(
                topP: 0.9,
                maxOutputTokens: 1000,
            );
            $params = $config->toRequestParams([]);

            expect($params)->toHaveKey('top_p')
                ->and($params)->toHaveKey('max_output_tokens')
                ->and($params)->not->toHaveKey('topP')
                ->and($params)->not->toHaveKey('maxOutputTokens');
        });
    });

    describe('getTools()', function (): void {
        it('returns empty array when useWebSearch is false and no tools', function (): void {
            $config = new AiGenerationConfig;

            expect($config->getTools())->toBe([]);
        });

        it('returns web search tool when useWebSearch is true', function (): void {
            config()->set('filament-ai-forms.web_search.country', 'GB');
            config()->set('filament-ai-forms.web_search.context_size', 'medium');

            $config = new AiGenerationConfig(useWebSearch: true);
            $tools = $config->getTools();

            expect($tools)->toHaveCount(1)
                ->and($tools[0]['type'])->toBe('web_search')
                ->and($tools[0]['user_location']['type'])->toBe('approximate')
                ->and($tools[0]['user_location']['country'])->toBe('GB');
        });

        it('returns custom tools when provided', function (): void {
            $customTools = [['type' => 'custom_tool']];
            $config = new AiGenerationConfig(tools: $customTools);

            expect($config->getTools())->toBe($customTools);
        });

        it('prefers custom tools over web search', function (): void {
            $customTools = [['type' => 'custom_tool']];
            $config = new AiGenerationConfig(
                useWebSearch: true,
                tools: $customTools,
            );

            expect($config->getTools())->toBe($customTools);
        });
    });

    describe('logging configuration', function (): void {
        it('returns default log path from config', function (): void {
            config()->set('filament-ai-forms.logging.path', '/custom/log/path');

            $config = new AiGenerationConfig;

            expect($config->getLogPath())->toBe('/custom/log/path');
        });

        it('returns custom logPath when set', function (): void {
            $config = new AiGenerationConfig(logPath: '/override/path');

            expect($config->getLogPath())->toBe('/override/path');
        });

        it('checks logging enabled from config', function (): void {
            config()->set('filament-ai-forms.logging.enabled', true);

            $config = new AiGenerationConfig(logEnabled: true);

            expect($config->isLoggingEnabled())->toBeTrue();
        });

        it('returns false when logEnabled is false', function (): void {
            config()->set('filament-ai-forms.logging.enabled', true);

            $config = new AiGenerationConfig(logEnabled: false);

            expect($config->isLoggingEnabled())->toBeFalse();
        });

        it('returns false when config logging is disabled', function (): void {
            config()->set('filament-ai-forms.logging.enabled', false);

            $config = new AiGenerationConfig(logEnabled: true);

            expect($config->isLoggingEnabled())->toBeFalse();
        });
    });
});
