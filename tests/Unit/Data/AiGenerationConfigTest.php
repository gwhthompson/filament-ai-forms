<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Agents\FormGenerationAgent;
use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;

describe('AiGenerationConfig', function (): void {
    describe('construction', function (): void {
        it('creates with defaults', function (): void {
            $config = new AiGenerationConfig;

            expect($config->agentClass)->toBeNull()
                ->and($config->systemPrompt)->toBeNull()
                ->and($config->logEnabled)->toBeTrue()
                ->and($config->logPath)->toBeNull();
        });

        it('creates with custom agent class', function (): void {
            $config = new AiGenerationConfig(agentClass: FormGenerationAgent::class);

            expect($config->agentClass)->toBe(FormGenerationAgent::class);
        });

        it('creates with system prompt', function (): void {
            $config = new AiGenerationConfig(systemPrompt: 'Custom prompt');

            expect($config->systemPrompt)->toBe('Custom prompt');
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

    describe('Spatie Data integration', function (): void {
        it('creates from array', function (): void {
            $config = AiGenerationConfig::from([
                'agentClass' => FormGenerationAgent::class,
                'systemPrompt' => 'Test prompt',
                'logEnabled' => false,
            ]);

            expect($config->agentClass)->toBe(FormGenerationAgent::class)
                ->and($config->systemPrompt)->toBe('Test prompt')
                ->and($config->logEnabled)->toBeFalse();
        });
    });
});
