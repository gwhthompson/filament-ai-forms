<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Services;

use Filament\Schemas\Components\Component;
use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Data\AiGenerationResult;
use Gwhthompson\FilamentAiForms\Services\Logging\AiGenerationLogger;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;
use Throwable;

/**
 * Generic service for AI-powered form data generation using OpenAI.
 *
 * This service is not tied to any specific model or form structure.
 * It can be used with any Filament form that has fields configured
 * with the aiSchema() mixin.
 *
 * @example
 * $service = app(AiFormGenerationService::class);
 * $result = $service->generate(
 *     config: AiGenerationConfig::from([...]),
 *     fields: $fieldMetadata,
 *     context: ['url' => 'https://example.com']
 * );
 */
class AiFormGenerationService
{
    public function __construct(
        private readonly FilamentToAiSchemaMapper $schemaMapper,
    ) {}

    /**
     * Generate form data using OpenAI.
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
            // Build schema and prompts from components
            $schemaConfig = $this->buildSchemaConfig($components, $config, $selectedFields);

            // Build user prompt with context
            $userPrompt = $this->buildUserPrompt($config, $context);

            // Build API request parameters
            $requestParams = $this->buildRequestParams(
                config: $config,
                schemaConfig: $schemaConfig,
                userPrompt: $userPrompt
            );

            // Call OpenAI API with retry for length violations
            $maxRetriesConfig = config('filament-ai-forms.retry.max_attempts', 2);
            $maxRetries = is_numeric($maxRetriesConfig) ? (int) $maxRetriesConfig : 2;
            $response = $this->generateWithRetry($requestParams, $schemaConfig['schema'], maxRetries: $maxRetries);

            // Check for incomplete response
            $status = property_exists($response, 'status') ? $response->status : null;
            if ($status === 'incomplete') {
                $incompleteDetails = property_exists($response, 'incompleteDetails') ? $response->incompleteDetails : null;
                $reason = 'unknown';
                if (is_object($incompleteDetails) && property_exists($incompleteDetails, 'reason')) {
                    $reasonValue = $incompleteDetails->reason;
                    $reason = is_scalar($reasonValue) ? (string) $reasonValue : 'unknown';
                }
                throw new RuntimeException('OpenAI response incomplete: '.$reason);
            }

            // Extract and decode content
            $content = property_exists($response, 'outputText') ? $response->outputText : null;

            if ($content === null || $content === '') {
                throw new RuntimeException('OpenAI returned no content');
            }

            $contentString = is_scalar($content) ? (string) $content : '';
            $data = json_decode($contentString, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data)) {
                throw new RuntimeException('OpenAI returned invalid JSON');
            }

            // Create result
            $duration = microtime(true) - $startTime;
            $modelValue = property_exists($response, 'model') ? $response->model : null;
            $responseModel = is_scalar($modelValue) ? (string) $modelValue : $config->model;
            $idValue = property_exists($response, 'id') ? $response->id : null;
            $responseId = is_scalar($idValue) ? (string) $idValue : null;

            /** @var array<string, mixed> $data */
            $result = new AiGenerationResult(
                data: $data,
                duration: $duration,
                model: $responseModel,
                fieldsGenerated: count($data),
                responseId: $responseId,
                schema: $schemaConfig['schema'],
                systemPrompt: $schemaConfig['systemPrompt'],
                userPrompt: $userPrompt,
                rawResponse: $this->buildRawResponseMetadata($response)
            );

            // Log if enabled
            if ($config->isLoggingEnabled()) {
                $logger = new AiGenerationLogger(
                    enabled: true,
                    path: $config->getLogPath()
                );

                $logPath = $logger->logSummary($config, $result, $context);
                $result = new AiGenerationResult(
                    data: $result->data,
                    duration: $result->duration,
                    model: $result->model,
                    fieldsGenerated: $result->fieldsGenerated,
                    responseId: $result->responseId,
                    logPath: $logPath,
                    schema: $result->schema,
                    systemPrompt: $result->systemPrompt,
                    userPrompt: $result->userPrompt,
                    rawResponse: $result->rawResponse
                );
            }

            return $result;
        } catch (Throwable $throwable) {
            Log::error('AI generation failed', [
                'error' => $throwable->getMessage(),
                'context' => $context,
                'duration' => round(microtime(true) - $startTime, 2).'s',
            ]);

            throw $throwable;
        }
    }

    /**
     * Build schema configuration from components.
     *
     * @param  array<int, Component>  $components
     * @param  array<int, string>|null  $selectedFields
     * @return array{schema: array<string, mixed>, systemPrompt: string, userPrompt: string}
     */
    protected function buildSchemaConfig(
        array $components,
        AiGenerationConfig $config,
        ?array $selectedFields
    ): array {
        return $this->schemaMapper->buildOpenAiConfig(
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
    protected function buildUserPrompt(AiGenerationConfig $config, array $context): string
    {
        if (isset($context['url']) && is_string($context['url'])) {
            return 'Create a profile for '.$context['url'].'.';
        }

        return 'Please generate the following data:';
    }

    /**
     * Build OpenAI API request parameters.
     *
     * @param  array{schema: array<string, mixed>, systemPrompt: string, userPrompt: string}  $schemaConfig
     * @return array<string, mixed>
     */
    protected function buildRequestParams(
        AiGenerationConfig $config,
        array $schemaConfig,
        string $userPrompt
    ): array {
        $params = [
            'model' => $config->model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [['type' => 'input_text', 'text' => $schemaConfig['systemPrompt']]],
                ],
                [
                    'role' => 'user',
                    'content' => [['type' => 'input_text', 'text' => $userPrompt]],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'generated_form_data',
                    'strict' => true,
                    'schema' => $schemaConfig['schema'],
                ],
            ],
            'reasoning' => (object) [],
            'tools' => $config->getTools(),
        ];

        // Only include optional parameters if they are set
        if ($config->temperature !== null) {
            $params['temperature'] = $config->temperature;
        }

        if ($config->topP !== null) {
            $params['top_p'] = $config->topP;
        }

        if ($config->maxOutputTokens !== null) {
            $params['max_output_tokens'] = $config->maxOutputTokens;
        }

        if ($config->store !== null) {
            $params['store'] = $config->store;
        }

        if ($config->include !== []) {
            $params['include'] = $config->include;
        }

        return $params;
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

    /**
     * Generate with automatic retry for schema violations.
     *
     * @param  array<string, mixed>  $requestParams
     * @param  array<string, mixed>  $schema
     */
    protected function generateWithRetry(array $requestParams, array $schema, int $maxRetries = 2): object
    {
        $attempt = 0;
        $validateSchemaConfig = config('filament-ai-forms.retry.validate_schema', true);
        $validateSchema = is_bool($validateSchemaConfig) ? $validateSchemaConfig : true;

        while ($attempt <= $maxRetries) {
            $response = OpenAI::responses()->create($requestParams);

            // Check if response is incomplete
            $status = property_exists($response, 'status') ? $response->status : null;
            if ($status === 'incomplete') {
                $incompleteDetails = property_exists($response, 'incompleteDetails') ? $response->incompleteDetails : null;
                $reason = 'unknown';
                if (is_object($incompleteDetails) && property_exists($incompleteDetails, 'reason')) {
                    $reasonValue = $incompleteDetails->reason;
                    $reason = is_scalar($reasonValue) ? (string) $reasonValue : 'unknown';
                }
                throw new RuntimeException('OpenAI response incomplete: '.$reason);
            }

            // Extract and validate content
            $content = property_exists($response, 'outputText') ? $response->outputText : null;

            if ($content === null || $content === '') {
                throw new RuntimeException('OpenAI returned no content');
            }

            $contentString = is_scalar($content) ? (string) $content : '';
            $data = json_decode($contentString, true, 512, JSON_THROW_ON_ERROR);

            // Skip validation if disabled
            if (! $validateSchema) {
                return $response;
            }

            // Validate against schema constraints
            /** @var array<string, mixed> $data */
            $violations = is_array($data) ? $this->validateSchemaConstraints($data, $schema) : ['Invalid JSON structure'];

            if ($violations === []) {
                // No violations - return successful response
                return $response;
            }

            // Log violations and retry if attempts remaining
            $attempt++;

            if ($attempt <= $maxRetries) {
                Log::warning('Schema violations detected, retrying', [
                    'attempt' => $attempt,
                    'violations' => $violations,
                ]);

                // Add violation feedback to next request
                $input = $requestParams['input'] ?? [];
                if (is_array($input)) {
                    $input[] = [
                        'role' => 'user',
                        'content' => [[
                            'type' => 'input_text',
                            'text' => 'Previous response had issues: '.implode(', ', $violations).'. Please regenerate following all constraints exactly.',
                        ]],
                    ];
                    $requestParams['input'] = $input;
                }
            } else {
                // Max retries exceeded - log and return anyway
                Log::warning('Max retries exceeded, accepting response with violations', [
                    'violations' => $violations,
                ]);

                return $response;
            }
        }

        throw new RuntimeException('Generation failed after retries');
    }

    /**
     * Validate data against schema constraints.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $schema
     * @return array<int, string> List of violation messages
     */
    protected function validateSchemaConstraints(array $data, array $schema): array
    {
        $violations = [];
        $properties = $schema['properties'] ?? [];

        if (! is_array($properties)) {
            return $violations;
        }

        foreach ($properties as $fieldName => $fieldSchema) {
            if (! is_string($fieldName) || ! is_array($fieldSchema)) {
                continue;
            }

            $value = $data[$fieldName] ?? null;

            if ($value === null) {
                continue;
            }

            // Check string constraints
            $fieldType = $fieldSchema['type'] ?? null;
            if ($fieldType === 'string' && is_string($value)) {
                $length = strlen($value);

                // Only validate length if constraints are defined
                $minLength = isset($fieldSchema['minLength']) && is_int($fieldSchema['minLength']) ? $fieldSchema['minLength'] : null;
                $maxLength = isset($fieldSchema['maxLength']) && is_int($fieldSchema['maxLength']) ? $fieldSchema['maxLength'] : null;

                if ($minLength !== null && $length < $minLength) {
                    $violations[] = $fieldName.' too short ('.$length.' < '.$minLength.' chars)';
                }

                if ($maxLength !== null && $length > $maxLength) {
                    $violations[] = $fieldName.' too long ('.$length.' > '.$maxLength.' chars)';
                }

                // Only check capitalization/punctuation for fields with minLength (assume those are prose)
                if ($minLength !== null && $minLength >= 50) {
                    if (! preg_match('/^[A-Z]/', $value)) {
                        $violations[] = $fieldName.' must start with capital letter';
                    }

                    if (! preg_match('/[.!?]$/', $value)) {
                        $violations[] = $fieldName.' must end with punctuation';
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Build raw response metadata for logging.
     *
     * @return array<string, mixed>|null
     */
    protected function buildRawResponseMetadata(mixed $response): ?array
    {
        if (! is_object($response)) {
            return null;
        }

        return [
            'id' => $response->id ?? null,
            'model' => $response->model ?? null,
            'status' => $response->status ?? null,
            'created' => $response->created ?? null,
        ];
    }
}
