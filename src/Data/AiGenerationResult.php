<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Data;

use Spatie\LaravelData\Data;

/**
 * Result DTO for AI generation.
 *
 * Contains the generated data plus metadata about the generation process.
 */
class AiGenerationResult extends Data
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $data,
        public float $duration,
        public string $model,
        public int $fieldsGenerated,
        public ?string $logPath = null,
        /** @var array<string, mixed>|null */
        public ?array $schema = null,
        public ?string $systemPrompt = null,
        public ?string $userPrompt = null,
    ) {}

    /** Get count of non-null fields in generated data. */
    public function nonNullFieldsCount(): int
    {
        return collect($this->data)
            ->filter(fn (mixed $value): bool => $value !== null)
            ->count();
    }
}
