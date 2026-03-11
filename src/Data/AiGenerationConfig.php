<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Data;

use Spatie\LaravelData\Data;

/**
 * Configuration DTO for AI generation.
 *
 * Agent classes carry model/provider/temperature config via PHP attributes,
 * so this DTO only tracks agent class selection and logging preferences.
 */
class AiGenerationConfig extends Data
{
    public function __construct(
        /** @var class-string|null */
        public ?string $agentClass = null,
        public ?string $systemPrompt = null,
        public bool $logEnabled = true,
        public ?string $logPath = null,
        /** @var array<int, mixed> */
        public array $tools = [],
    ) {}

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
