<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Data\AiGenerationResult;
use Gwhthompson\FilamentAiForms\Services\Logging\AiGenerationLogger;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->logPath = sys_get_temp_dir().'/filament-ai-forms-test-logs';

    // Clean up any existing test logs
    if (is_dir($this->logPath)) {
        array_map('unlink', glob("{$this->logPath}/*.md") ?: []);
    }
});

afterEach(function (): void {
    // Clean up test logs
    if (is_dir($this->logPath)) {
        array_map('unlink', glob("{$this->logPath}/*.md") ?: []);
        @rmdir($this->logPath);
    }
});

describe('AiGenerationLogger', function (): void {
    describe('logSummary()', function (): void {
        it('returns null when disabled', function (): void {
            $logger = new AiGenerationLogger(
                enabled: false,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: ['title' => 'Test'],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 1,
            );

            $logPath = $logger->logSummary($config, $result);

            expect($logPath)->toBeNull();
        });

        it('writes log file when enabled', function (): void {
            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: ['title' => 'Generated Title'],
                duration: 1.5,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 1,
                systemPrompt: 'You are helpful',
                userPrompt: 'Generate a title',
            );

            $logPath = $logger->logSummary($config, $result);

            expect($logPath)->not->toBeNull()
                ->and(file_exists($logPath))->toBeTrue();

            $content = file_get_contents($logPath);
            expect($content)
                ->toContain('# AI Generation Log')
                ->toContain('gpt-4.1-mini')
                ->toContain('Generated Title');
        });

        it('includes context URL in log', function (): void {
            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: ['field' => 'value'],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 1,
            );

            $logPath = $logger->logSummary($config, $result, [
                'url' => 'https://example.com/page',
            ]);

            $content = file_get_contents($logPath);
            expect($content)->toContain('https://example.com/page');
        });

        it('generates filename with URL domain suffix', function (): void {
            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: [],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 0,
            );

            $logPath = $logger->logSummary($config, $result, [
                'url' => 'https://test-domain.com/path',
            ]);

            // Str::slug() removes dots without hyphens: test-domain.com -> test-domaincom
            expect($logPath)->toContain('_test-domaincom.md');
        });

        it('generates filename with domain suffix', function (): void {
            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: [],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 0,
            );

            $logPath = $logger->logSummary($config, $result, [
                'domain' => 'my-domain.co.uk',
            ]);

            // Str::slug() removes dots: my-domain.co.uk -> my-domaincouk
            expect($logPath)->toContain('_my-domaincouk.md');
        });

        it('logs debug message after writing', function (): void {
            Log::spy();

            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: [],
                duration: 2.5,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 3,
            );

            $logger->logSummary($config, $result);

            Log::shouldHaveReceived('debug')
                ->with('AI generation logged', \Mockery::on(fn ($context) => $context['fields_generated'] === 3
                        && $context['duration'] === 2.5
                ))
                ->once();
        });

        it('handles write errors gracefully', function (): void {
            Log::spy();

            // Use an invalid path that will fail to write
            $logger = new AiGenerationLogger(
                enabled: true,
                path: '/nonexistent/invalid/path/that/cannot/be/created',
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: [],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 0,
            );

            $logPath = $logger->logSummary($config, $result);

            expect($logPath)->toBeNull();

            Log::shouldHaveReceived('warning')
                ->with('Failed to write AI generation log', \Mockery::type('array'))
                ->once();
        });
    });

    describe('markdown content', function (): void {
        it('includes schema in log', function (): void {
            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: [],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 0,
                schema: [
                    'type' => 'object',
                    'properties' => ['title' => ['type' => 'string']],
                ],
            );

            $logPath = $logger->logSummary($config, $result);
            $content = file_get_contents($logPath);

            expect($content)
                ->toContain('## Schema')
                ->toContain('"type": "object"')
                ->toContain('"properties"');
        });

        it('includes prompts in log', function (): void {
            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: [],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 0,
                systemPrompt: 'You are an expert copywriter',
                userPrompt: 'Generate content for this venue',
            );

            $logPath = $logger->logSummary($config, $result);
            $content = file_get_contents($logPath);

            expect($content)
                ->toContain('## System Prompt')
                ->toContain('You are an expert copywriter')
                ->toContain('## User Prompt')
                ->toContain('Generate content for this venue');
        });

        it('formats duration in milliseconds', function (): void {
            $logger = new AiGenerationLogger(
                enabled: true,
                path: $this->logPath,
            );

            $config = new AiGenerationConfig;
            $result = new AiGenerationResult(
                data: [],
                duration: 1.234,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 0,
            );

            $logPath = $logger->logSummary($config, $result);
            $content = file_get_contents($logPath);

            expect($content)->toContain('1234ms');
        });
    });
});
