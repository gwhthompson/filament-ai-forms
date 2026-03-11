<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Agents;

use Gwhthompson\FilamentAiForms\Support\JsonSchemaConverter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class FormGenerationAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * @param  array<string, mixed>  $rawSchema
     * @param  array<int, mixed>  $tools
     */
    public function __construct(
        private string $systemInstructions = 'You are an AI assistant generating structured data.',
        private array $rawSchema = [],
        private array $tools = [],
    ) {}

    public function instructions(): string
    {
        return $this->systemInstructions;
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return JsonSchemaConverter::convert($this->rawSchema, $schema);
    }

    /** @return iterable<mixed> */
    public function tools(): iterable
    {
        return $this->tools;
    }
}
