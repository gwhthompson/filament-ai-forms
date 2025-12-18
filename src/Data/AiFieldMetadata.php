<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Data;

use Spatie\LaravelData\Data;

/**
 * Field metadata DTO for AI schema generation.
 *
 * @example
 * AiFieldMetadata::from([
 *     'name' => 'email',
 *     'label' => 'Email Address',
 *     'description' => 'User email',
 *     'prompt' => 'Extract the primary contact email'
 * ])
 */
class AiFieldMetadata extends Data
{
    public function __construct(
        public string $name,
        public string $label,
        public ?string $description = null,
        public ?string $prompt = null,
        /** @var array<int, string> */
        public array $examples = [],
        /** @var array<int, string>|null */
        public ?array $options = null,
    ) {}

    /** Check if field has enum options defined. */
    public function hasOptions(): bool
    {
        return $this->options !== null && $this->options !== [];
    }

    /** Get effective prompt (uses description if prompt is null). */
    public function getEffectivePrompt(): ?string
    {
        return $this->prompt ?? $this->description;
    }
}
