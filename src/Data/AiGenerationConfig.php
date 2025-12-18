<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Data;

use Gwhthompson\FilamentAiForms\Enums\ReasoningEffort;
use Gwhthompson\FilamentAiForms\Enums\ServiceTier;
use Gwhthompson\FilamentAiForms\Enums\Verbosity;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Configuration DTO for OpenAI Responses API.
 *
 * Uses Spatie attributes for automatic API payload transformation:
 * - #[MapOutputName] with SnakeCaseMapper converts camelCase to snake_case
 * - #[Computed] properties are calculated from hidden user-facing config
 * - #[Hidden] properties are excluded from toArray() output
 *
 * @example
 * $config = new AiGenerationConfig(
 *     model: 'gpt-4.1-mini',
 *     verbosity: Verbosity::Low,
 *     reasoningEffort: ReasoningEffort::Minimal,
 * );
 * $params = $config->toRequestParams($input);
 */
#[MapOutputName(SnakeCaseMapper::class)]
class AiGenerationConfig extends Data
{
    /** @var array{verbosity?: string, format?: array<string, mixed>}|null */
    #[Computed]
    public ?array $text;

    /** @var array{effort: string}|null */
    #[Computed]
    public ?array $reasoning;

    public function __construct(
        public string $model = 'gpt-4.1-mini',
        #[Between(0, 2)]
        public ?float $temperature = null,
        #[Between(0, 1)]
        public ?float $topP = null,
        #[Min(1)]
        public ?int $maxOutputTokens = null,
        #[Between(0, 20)]
        public ?int $topLogprobs = null,
        public ?ServiceTier $serviceTier = null,
        public ?bool $store = null,
        public bool $stream = false,

        /** @var array<int, string> */
        public array $include = [],

        // User-facing config (hidden from output, used to compute text/reasoning)
        #[Hidden]
        public ?Verbosity $verbosity = null,

        /** @var array{type: string, name: string, schema: array<string, mixed>, strict?: bool}|null */
        #[Hidden]
        public ?array $jsonSchema = null,
        #[Hidden]
        public ?ReasoningEffort $reasoningEffort = null,

        // Non-API properties
        #[Hidden]
        public ?string $systemPrompt = null,

        /**
         * Enable OpenAI web search tool.
         *
         * IMPORTANT: Defaults to false. Services requiring web search must
         * explicitly set useWebSearch: true.
         */
        #[Hidden]
        public bool $useWebSearch = false,

        /** @var array<int, array{type: string}> */
        #[Hidden]
        public array $tools = [],
        #[Hidden]
        public bool $logEnabled = true,
        #[Hidden]
        public ?string $logPath = null,
    ) {
        $this->text = match (true) {
            $this->jsonSchema !== null => ['format' => $this->jsonSchema],
            $this->verbosity !== null => ['verbosity' => $this->verbosity->value],
            default => null,
        };

        $this->reasoning = $this->reasoningEffort !== null
            ? ['effort' => $this->reasoningEffort->value]
            : null;
    }

    /**
     * Build request parameters for OpenAI Responses API.
     *
     * @param  array<int, array{role: string, content: mixed}>  $input
     * @return array<string, mixed>
     */
    public function toRequestParams(array $input): array
    {
        /** @var array<string, mixed> $params */
        $params = collect($this->toArray())
            ->filter(fn (mixed $v): bool => $v !== null && $v !== [] && $v !== false)
            ->toArray();

        $params['input'] = $input;

        if ($tools = $this->getTools()) {
            $params['tools'] = $tools;
        }

        return $params;
    }

    /**
     * Get tools array for OpenAI API.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTools(): array
    {
        if ($this->useWebSearch && $this->tools === []) {
            $countryConfig = config('filament-ai-forms.web_search.country', 'GB');
            $country = is_string($countryConfig) ? $countryConfig : 'GB';

            $contextConfig = config('filament-ai-forms.web_search.context_size', 'medium');
            $contextSize = is_string($contextConfig) ? $contextConfig : 'medium';

            return [[
                'type' => 'web_search',
                'user_location' => ['type' => 'approximate', 'country' => $country],
                'search_context_size' => $contextSize,
            ]];
        }

        return $this->tools;
    }

    /** Get log path from config or default. */
    public function getLogPath(): string
    {
        if ($this->logPath !== null) {
            return $this->logPath;
        }

        $configValue = config('filament-ai-forms.logging.path', storage_path('logs/ai-generation'));

        return is_string($configValue) ? $configValue : storage_path('logs/ai-generation');
    }

    /** Check if logging is enabled. */
    public function isLoggingEnabled(): bool
    {
        return $this->logEnabled && (bool) config('filament-ai-forms.logging.enabled', true);
    }
}
