<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Services;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\StateCasts\BooleanStateCast;
use Filament\Schemas\Components\StateCasts\EnumArrayStateCast;
use Filament\Schemas\Components\StateCasts\EnumStateCast;
use Filament\Schemas\Components\StateCasts\NumberStateCast;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;
use Filament\Schemas\Components\StateCasts\OptionStateCast;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FilamentToAiSchemaMapper
{
    protected const VALIDATION_FORMAT_MAPPING = [
        'email' => 'email',
        'url' => 'uri',
        'uuid' => 'uuid',
        'ipv4' => 'ipv4',
        'ipv6' => 'ipv6',
    ];

    /**
     * @param  array<int, Component>  $components
     * @param  array<int, string>|null  $selectedFields  Filter to only these field names (null = all fields)
     * @return array{schema: array<string, mixed>, systemPrompt: string, userPrompt: string}
     */
    public function buildOpenAiConfig(array $components, string $basePrompt = '', ?array $selectedFields = null): array
    {
        /** @var array<string, array<string, mixed>> $properties */
        $properties = [];
        /** @var array<int, string> $required */
        $required = [];

        foreach ($components as $component) {
            if (! $component->isAiEnabled()) {
                continue;
            }

            $fieldName = $component->getName();

            // If selectedFields is provided, only include fields in the selection
            if ($selectedFields !== null && ! in_array($fieldName, $selectedFields, true)) {
                continue;
            }

            $properties[$fieldName] = $this->buildComponentSchema($component);

            // Add to required if aiSchema(required: true)
            if ($component->getAiRequired()) {
                $required[] = $fieldName;
            }
        }

        if ($properties === []) {
            throw new InvalidArgumentException('No AI-generatable components found in schema');
        }

        return [
            'schema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required,
                'additionalProperties' => false,
            ],
            'systemPrompt' => $this->buildSystemPrompt([], $basePrompt),
            'userPrompt' => '',
        ];
    }

    /** @return array<string, mixed> */
    protected function buildComponentSchema(Component $component): array
    {
        $stateCasts = $component->getStateCasts();
        $stateCast = $stateCasts[0] ?? null;

        $schema = match (true) {
            $stateCast instanceof OptionStateCast,
            $stateCast instanceof EnumStateCast => $this->buildEnumSchema($component, $stateCast),

            $stateCast instanceof OptionsArrayStateCast,
            $stateCast instanceof EnumArrayStateCast => $this->buildEnumArraySchema($component, $stateCast),

            $stateCast instanceof BooleanStateCast => $this->buildBooleanSchema($component),

            $stateCast instanceof NumberStateCast => $this->buildNumericSchema($component),

            default => $this->buildStringSchema($component),
        };

        // Add description if available
        if ($description = $component->getAiDescription()) {
            $schema['description'] = $description;
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    protected function buildEnumSchema(Component $component, OptionStateCast|EnumStateCast $stateCast): array
    {
        $options = $this->getEnumOptions($component);

        if ($options === []) {
            $componentName = $component->getName();
            throw new InvalidArgumentException('Component '.$componentName.' has no options for enum schema');
        }

        // Determine enum type from first option value
        $firstValue = array_values($options)[0];
        $enumType = match (true) {
            is_int($firstValue) => 'integer',
            default => 'string',
        };

        $schema = [
            'type' => $enumType,
            'enum' => array_values($options),
        ];

        // Handle nullable
        if ($this->isNullable($component)) {
            $schema['type'] = [$enumType, 'null'];
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    protected function buildEnumArraySchema(Component $component, OptionsArrayStateCast|EnumArrayStateCast $stateCast): array
    {
        $options = $this->getEnumOptions($component);

        if ($options === []) {
            $componentName = $component->getName();
            throw new InvalidArgumentException('Component '.$componentName.' has no options for enum array schema');
        }

        // Determine item type from first option value
        $firstValue = array_values($options)[0];
        $itemType = is_int($firstValue) ? 'integer' : 'string';

        $schema = [
            'type' => 'array',
            'items' => [
                'type' => $itemType,
                'enum' => array_values($options),
            ],
        ];

        // Apply array constraints from validation rules
        $this->applyArrayConstraints($schema, $component);

        // Handle nullable
        if ($this->isNullable($component)) {
            $schema['type'] = ['array', 'null'];
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    protected function buildBooleanSchema(Component $component): array
    {
        $schema = ['type' => 'boolean'];

        // Handle nullable
        if ($this->isNullable($component)) {
            $schema['type'] = ['boolean', 'null'];
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    protected function buildNumericSchema(Component $component): array
    {
        $rules = $this->parseValidationRules($component);

        // Check if integer validation rule exists
        $isInteger = collect($rules)->contains(
            fn ($rule): bool => $rule === 'integer' || $rule === 'int'
        );

        $numericType = $isInteger ? 'integer' : 'number';
        $schema = ['type' => $numericType];

        $this->applyNumericConstraints($schema, $component);

        // Handle nullable
        if ($this->isNullable($component)) {
            $schema['type'] = [$numericType, 'null'];
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    protected function buildStringSchema(Component $component): array
    {
        $schema = ['type' => 'string'];

        $this->applyStringConstraints($schema, $component);

        // Note: pattern validation not used because it conflicts with minLength/maxLength
        // Capitalization enforced through prompt instructions instead

        // Handle nullable
        if ($this->isNullable($component)) {
            $schema['type'] = ['string', 'null'];
        }

        return $schema;
    }

    /** @param  array<string, mixed>  &$schema */
    protected function applyStringConstraints(array &$schema, Component $component): void
    {
        $rules = $this->parseValidationRules($component);

        foreach ($rules as $rule) {
            // Only keep format handling - minLength/maxLength NOT supported by OpenAI Structured Outputs
            if (is_string($rule) && isset(self::VALIDATION_FORMAT_MAPPING[$rule])) {
                $schema['format'] = self::VALIDATION_FORMAT_MAPPING[$rule];
            }
        }

        // Use pattern from aiSchema() if provided
        // Pattern IS supported by OpenAI Structured Outputs
        if ($pattern = $component->getAiPattern()) {
            $schema['pattern'] = $pattern;
        }
    }

    /** @param  array<string, mixed>  &$schema */
    protected function applyNumericConstraints(array &$schema, Component $component): void
    {
        $rules = $this->parseValidationRules($component);

        foreach ($rules as $rule) {
            match (true) {
                is_string($rule) && str_starts_with($rule, 'min:') => $schema['minimum'] = (int) Str::after($rule, 'min:'),

                is_string($rule) && str_starts_with($rule, 'max:') => $schema['maximum'] = (int) Str::after($rule, 'max:'),

                is_string($rule) && str_starts_with($rule, 'multiple_of:') => $schema['multipleOf'] = (int) Str::after($rule, 'multiple_of:'),

                default => null,
            };
        }
    }

    /** @param  array<string, mixed>  &$schema */
    protected function applyArrayConstraints(array &$schema, Component $component): void
    {
        $rules = $this->parseValidationRules($component);

        foreach ($rules as $rule) {
            match (true) {
                is_string($rule) && str_starts_with($rule, 'min:') => $schema['minItems'] = (int) Str::after($rule, 'min:'),

                is_string($rule) && str_starts_with($rule, 'max:') => $schema['maxItems'] = (int) Str::after($rule, 'max:'),

                default => null,
            };
        }
    }

    /** @return array<int, mixed> */
    protected function parseValidationRules(Component $component): array
    {
        $rules = $component->getValidationRules();

        // Handle string format: 'required|email|max:255'
        if (is_string($rules)) {
            return array_filter(explode('|', $rules));
        }

        // Handle array format: ['required', 'email', 'max:255']
        if (! is_array($rules)) {
            return [];
        }

        /** @var array<int, mixed> */
        return collect($rules)
            ->map(fn (mixed $rule): mixed => is_object($rule) ? $rule::class : $rule)
            ->filter()
            ->values()
            ->all();
    }

    protected function isNullable(Component $component): bool
    {
        // If aiSchema(required: true), NEVER nullable in schema
        if ($component->getAiRequired()) {
            return false;
        }

        // For aiSchema(required: false), check validation rules
        $rules = $this->parseValidationRules($component);

        return collect($rules)->contains(
            fn (mixed $rule): bool => $rule === 'nullable' ||
            (is_string($rule) && str_contains($rule, 'Nullable'))
        );
    }

    /** @return array<string|int, mixed> */
    protected function getEnumOptions(Component $component): array
    {
        // Try to get options from the component
        if (method_exists($component, 'getOptions')) {
            $options = $component->getOptions();

            if (is_callable($options)) {
                $options = $options();
            }

            /** @var array<string|int, mixed> */
            return is_array($options) ? $options : [];
        }

        return [];
    }

    /**
     * @param  array<string, string>  $fieldDescriptions
     * @param  array<string, array<int, string>>  $enumConstraints
     */
    protected function buildSystemPrompt(array $fieldDescriptions, string $basePrompt, array $enumConstraints = []): string
    {
        $prompt = $basePrompt !== '' ? $basePrompt."\n\n" : '';

        $prompt .= 'Generate structured data according to the JSON schema provided. ';
        $prompt .= 'Ensure all required fields are populated with appropriate values. ';
        $prompt .= 'For text fields, use proper capitalization and punctuation.';

        if ($fieldDescriptions !== []) {
            $prompt .= "\n\nField guidance:\n";
            foreach ($fieldDescriptions as $field => $description) {
                $prompt .= "- {$field}: {$description}\n";
            }
        }

        if ($enumConstraints !== []) {
            $prompt .= "\n\nEnum constraints:\n";
            foreach ($enumConstraints as $field => $options) {
                $prompt .= "- {$field}: must be one of [".implode(', ', $options)."]\n";
            }
        }

        return $prompt;
    }

    /** @param  array<string, string>  $fieldGuidance */
    protected function buildUserPrompt(array $fieldGuidance): string
    {
        if ($fieldGuidance === []) {
            return '';
        }

        $prompt = "Please pay special attention to the following guidance:\n\n";

        foreach ($fieldGuidance as $field => $guidance) {
            $prompt .= "- {$field}: {$guidance}\n";
        }

        return $prompt;
    }
}
