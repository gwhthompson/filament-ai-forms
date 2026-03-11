<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Services\Logging;

use Gwhthompson\FilamentAiForms\Data\AiGenerationConfig;
use Gwhthompson\FilamentAiForms\Data\AiGenerationResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Configurable logger for AI generation operations.
 *
 * Writes concise summary documents instead of verbose inline logging.
 */
class AiGenerationLogger
{
    public function __construct(
        private readonly bool $enabled,
        private readonly string $path
    ) {}

    /**
     * Log a summary of the generation operation.
     *
     * @param  array<string, mixed>  $context
     */
    public function logSummary(
        AiGenerationConfig $config,
        AiGenerationResult $result,
        array $context = []
    ): ?string {
        if (! $this->enabled) {
            return null;
        }

        try {
            $markdown = $this->buildSummaryMarkdown($config, $result, $context);
            $filePath = $this->writeMarkdownFile($markdown, $context);

            Log::debug('AI generation logged', [
                'path' => $filePath,
                'fields_generated' => $result->fieldsGenerated,
                'duration' => $result->duration,
            ]);

            return $filePath;
        } catch (Throwable $throwable) {
            Log::warning('Failed to write AI generation log', [
                'error' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build markdown summary document.
     *
     * @param  array<string, mixed>  $context
     */
    protected function buildSummaryMarkdown(
        AiGenerationConfig $config,
        AiGenerationResult $result,
        array $context
    ): string {
        $timestamp = now()->format('Y-m-d H:i:s');
        $url = isset($context['url']) && is_string($context['url']) ? $context['url'] : 'N/A';
        $durationMs = round($result->duration * 1000);

        $agentClass = $config->agentClass ?? 'default';

        return <<<MARKDOWN
        # AI Generation Log

        **Generated**: {$timestamp}
        **Agent**: {$agentClass}
        **Model**: {$result->model}
        **Duration**: {$durationMs}ms
        **Fields Generated**: {$result->fieldsGenerated}

        ## Context

        - **URL**: {$url}

        ## System Prompt

        ```
        {$result->systemPrompt}
        ```

        ## User Prompt

        ```
        {$result->userPrompt}
        ```

        ## Generated Data

        ```json
        {$this->formatJson($result->data)}
        ```

        ## Schema

        ```json
        {$this->formatJson($result->schema)}
        ```
        MARKDOWN;
    }

    /**
     * Format array as pretty JSON.
     *
     * @param  array<string, mixed>|null  $data
     */
    protected function formatJson(?array $data): string
    {
        if ($data === null) {
            return 'null';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Write markdown file to disk.
     *
     * @param  array<string, mixed>  $context
     */
    protected function writeMarkdownFile(string $markdown, array $context): string
    {
        // Generate filename from timestamp and context
        $timestamp = now()->format('Y-m-d_His');
        $suffix = $this->generateFilenameSuffix($context);
        $filename = "{$timestamp}{$suffix}.md";

        // Ensure directory exists
        if (! file_exists($this->path)) {
            mkdir($this->path, 0755, true);
        }

        // Write file
        $filePath = "{$this->path}/{$filename}";
        file_put_contents($filePath, $markdown);

        return $filePath;
    }

    /**
     * Generate filename suffix from context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function generateFilenameSuffix(array $context): string
    {
        // Use URL domain if available
        if (isset($context['url']) && is_string($context['url'])) {
            $host = parse_url($context['url'], PHP_URL_HOST);
            if (is_string($host)) {
                return '_'.Str::slug($host);
            }
        }

        // Use domain if available
        if (isset($context['domain']) && is_string($context['domain'])) {
            return '_'.Str::slug($context['domain']);
        }

        return '';
    }
}
