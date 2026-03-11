<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Services;

use Filament\Schemas\Components\Component;
use Gwhthompson\FilamentAiForms\Agents\FormGenerationAgent;
use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Data\AiGenerationResult;
use Gwhthompson\FilamentAiForms\Exceptions\AiResponseParseException;
use Gwhthompson\FilamentAiForms\Exceptions\AiServiceTimeoutException;
use Gwhthompson\FilamentAiForms\Services\Logging\AiGenerationLogger;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

/**
 * Generic service for AI-powered form data generation.
 *
 * Uses the Laravel AI SDK's Agent pattern for provider-agnostic generation.
 */
class AiFormGenerationService
{
    public function __construct(
        private readonly FilamentToAiSchemaMapper $schemaMapper,
    ) {}

    /**
     * Generate form data using an AI agent.
     *
     * @param  AiGenerationConfig  $config  Generation configuration
     * @param  array<int, Component>  $components  Filament form components
     * @param  array<string, mixed>  $context  Additional context (url, domain, etc)
     * @param  array<int, string>|null  $selectedFields  Optional specific fields to generate
     *
     * @throws RuntimeException If AI generation fails
     */
    public function generate(
        AiGenerationConfig $config,
        array $components,
        array $context = [],
        ?array $selectedFields = null
    ): AiGenerationResult {
        $startTime = microtime(true);

        try {
            $schemaConfig = $this->buildSchemaConfig($components, $config, $selectedFields);
            $userPrompt = $this->buildUserPrompt($context);

            // Resolve agent
            /** @var FormGenerationAgent $agent */
            $agent = $config->agentClass !== null
                ? app($config->agentClass)
                : new FormGenerationAgent(
                    systemInstructions: $schemaConfig['systemPrompt'],
                    rawSchema: $schemaConfig['schema'],
                    tools: $config->tools,
                );

            // Call agent — structured output returns array-accessible response
            $response = $agent->prompt($userPrompt);

            if (! $response instanceof StructuredAgentResponse) {
                throw new RuntimeException('Agent must implement HasStructuredOutput for form generation.');
            }

            // Extract data from structured response
            /** @var array<string, mixed> $data */
            $data = $response->toArray();

            if ($data === []) {
                throw new RuntimeException('AI returned no data');
            }

            $duration = microtime(true) - $startTime;
            $modelName = $response->meta->model ?? 'unknown';

            $result = new AiGenerationResult(
                data: $data,
                duration: $duration,
                model: $modelName,
                fieldsGenerated: count($data),
                schema: $schemaConfig['schema'],
                systemPrompt: $schemaConfig['systemPrompt'],
                userPrompt: $userPrompt,
            );

            // Log if enabled
            if ($config->isLoggingEnabled()) {
                $logger = new AiGenerationLogger(
                    enabled: true,
                    path: $config->getLogPath()
                );

                $result->logPath = $logger->logSummary($config, $result, $context);
            }

            return $result;
        } catch (Throwable $throwable) {
            Log::error('AI generation failed', [
                'error' => $throwable->getMessage(),
                'context' => $context,
                'duration' => round(microtime(true) - $startTime, 2).'s',
            ]);

            throw match (true) {
                str_contains($throwable->getMessage(), 'timeout') => new AiServiceTimeoutException($throwable->getMessage(), $throwable),
                str_contains($throwable->getMessage(), 'JSON') => new AiResponseParseException($throwable->getMessage(), $throwable),
                default => $throwable,
            };
        }
    }

    /**
     * Build schema configuration from components.
     *
     * @param  array<int, Component>  $components
     * @param  array<int, string>|null  $selectedFields
     * @return array{schema: array<string, mixed>, systemPrompt: string}
     */
    protected function buildSchemaConfig(
        array $components,
        AiGenerationConfig $config,
        ?array $selectedFields
    ): array {
        return $this->schemaMapper->buildSchemaConfig(
            components: $components,
            basePrompt: $config->systemPrompt ?? 'You are an AI assistant generating structured data.',
            selectedFields: $selectedFields
        );
    }

    /**
     * Build user prompt with context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function buildUserPrompt(array $context): string
    {
        if (isset($context['url']) && is_string($context['url'])) {
            return 'Create a profile for '.$context['url'].'.';
        }

        return 'Please generate the following data:';
    }

    /**
     * Merge AI-generated data with existing record data.
     *
     * @param  array<string, mixed>  $aiData  AI-generated data
     * @param  array<string, mixed>  $existingData  Existing record data
     * @param  string  $mergeMode  'replace', 'merge', or 'enhance'
     * @return array<string, mixed> Merged data
     */
    public function mergeWithExistingData(array $aiData, array $existingData, string $mergeMode = 'replace'): array
    {
        return match ($mergeMode) {
            'replace' => $aiData,
            'merge' => $this->mergeModeStrategy($aiData, $existingData),
            'enhance' => $this->enhanceModeStrategy($aiData, $existingData),
            default => $aiData,
        };
    }

    /**
     * Merge mode: Keep non-empty existing fields, use AI data for empty fields.
     *
     * @param  array<string, mixed>  $aiData
     * @param  array<string, mixed>  $existingData
     * @return array<string, mixed>
     */
    protected function mergeModeStrategy(array $aiData, array $existingData): array
    {
        /** @var array<string, mixed> */
        return collect($aiData)
            ->mapWithKeys(function (mixed $aiValue, int|string $key) use ($existingData): array {
                $stringKey = (string) $key;
                $existingValue = $existingData[$stringKey] ?? null;

                // Keep existing non-empty values
                if (! in_array($existingValue, [null, '', []], true)) {
                    return [$stringKey => $existingValue];
                }

                return [$stringKey => $aiValue];
            })
            ->toArray();
    }

    /**
     * Enhance mode: Only fill fields that are empty in existing data.
     *
     * @param  array<string, mixed>  $aiData
     * @param  array<string, mixed>  $existingData
     * @return array<string, mixed>
     */
    protected function enhanceModeStrategy(array $aiData, array $existingData): array
    {
        /** @var array<string, mixed> */
        return collect($existingData)
            ->mapWithKeys(function (mixed $existingValue, int|string $key) use ($aiData): array {
                $stringKey = (string) $key;

                // Only add AI data if existing field is empty
                if (in_array($existingValue, [null, '', []], true)) {
                    return [$stringKey => $aiData[$stringKey] ?? $existingValue];
                }

                return [$stringKey => $existingValue];
            })
            ->merge(
                // Add any AI fields that don't exist in existing data
                collect($aiData)->reject(fn (mixed $value, int|string $key): bool => array_key_exists((string) $key, $existingData))
            )
            ->toArray();
    }
}
