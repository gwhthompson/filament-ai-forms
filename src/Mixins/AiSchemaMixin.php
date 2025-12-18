<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Mixins;

use Closure;
use Filament\Schemas\Components\Component;

/**
 * Mixin for adding AI schema generation capabilities to Filament components.
 *
 * Provides a declarative API for annotating form fields with AI generation config.
 *
 * @mixin Component
 *
 * @example
 * TextInput::make('name')
 *     ->aiSchema(
 *         enabled: true,
 *         description: 'Brand name',
 *         prompt: 'Extract the official name',
 *         required: true,  // Default - AI must provide value
 *         examples: ['Starbucks', 'McDonald\'s']
 *     )
 */
final class AiSchemaMixin
{
    /** Configure AI schema generation for this field. */
    public function aiSchema(): Closure
    {
        return fn (
            bool $enabled = true,
            ?string $description = null,
            ?string $prompt = null,
            bool $required = true,
            array $examples = [],
            ?string $pattern = null,
        ): Component => $this->meta('ai_schema', [
            'enabled' => $enabled,
            'description' => $description,
            'prompt' => $prompt,
            'required' => $required,
            'examples' => $examples,
            'pattern' => $pattern,
        ]);
    }

    /** Get the AI schema configuration for this field. */
    public function getAiSchema(): Closure
    {
        return function (): ?array {
            $schema = $this->getMeta('ai_schema');

            return is_array($schema) ? $schema : null;
        };
    }

    /** Check if AI generation is enabled for this field. */
    public function isAiEnabled(): Closure
    {
        return function (): bool {
            $schema = $this->getMeta('ai_schema');

            if (! is_array($schema)) {
                return false;
            }

            return ($schema['enabled'] ?? false) === true;
        };
    }

    /** Get the description for AI generation. */
    public function getAiDescription(): Closure
    {
        return function (): ?string {
            $schema = $this->getMeta('ai_schema');

            if (! is_array($schema)) {
                return null;
            }

            $description = $schema['description'] ?? null;

            return is_string($description) ? $description : null;
        };
    }

    /** Get the prompt guidance for AI generation. */
    public function getAiPrompt(): Closure
    {
        return function (): ?string {
            $schema = $this->getMeta('ai_schema');

            if (! is_array($schema)) {
                return null;
            }

            $prompt = $schema['prompt'] ?? null;

            return is_string($prompt) ? $prompt : null;
        };
    }

    /** Get whether this field is required for AI generation. */
    public function getAiRequired(): Closure
    {
        return function (): bool {
            $schema = $this->getMeta('ai_schema');

            if (! is_array($schema)) {
                return true;
            }

            return ($schema['required'] ?? true) === true;
        };
    }

    /** Get the examples for AI generation. */
    public function getAiExamples(): Closure
    {
        return function (): array {
            $schema = $this->getMeta('ai_schema');

            if (! is_array($schema)) {
                return [];
            }

            $examples = $schema['examples'] ?? [];

            return is_array($examples) ? $examples : [];
        };
    }

    /** Get the pattern constraint for AI generation. */
    public function getAiPattern(): Closure
    {
        return function (): ?string {
            $schema = $this->getMeta('ai_schema');

            if (! is_array($schema)) {
                return null;
            }

            $pattern = $schema['pattern'] ?? null;

            return is_string($pattern) ? $pattern : null;
        };
    }

    /** Get the effective prompt (uses description if prompt is null). */
    public function getEffectiveAiPrompt(): Closure
    {
        return function (): ?string {
            $schema = $this->getMeta('ai_schema');

            if (! is_array($schema)) {
                return null;
            }

            $prompt = $schema['prompt'] ?? null;
            if (is_string($prompt)) {
                return $prompt;
            }

            $description = $schema['description'] ?? null;

            return is_string($description) ? $description : null;
        };
    }
}
