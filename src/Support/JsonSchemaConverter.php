<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Support;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * Converts raw JSON Schema arrays (from FilamentToAiSchemaMapper) to the
 * Laravel AI SDK's fluent JsonSchema builder format for HasStructuredOutput.
 */
class JsonSchemaConverter
{
    /**
     * Convert raw JSON Schema properties to builder format.
     *
     * @param  array<string, mixed>  $rawSchema
     * @return array<string, Type>
     */
    public static function convert(array $rawSchema, JsonSchema $schema): array
    {
        $result = [];
        /** @var array<int, string> $required */
        $required = $rawSchema['required'] ?? [];

        /** @var array<string, array<string, mixed>> $properties */
        $properties = $rawSchema['properties'] ?? [];

        foreach ($properties as $field => $definition) {
            $builder = self::buildField($schema, $definition);

            if (in_array($field, $required, true)) {
                $builder->required();
            }

            $result[$field] = $builder;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private static function buildField(JsonSchema $schema, array $def): Type
    {
        /** @var string|array<int, string> $type */
        $type = $def['type'];
        $nullable = false;

        if (is_array($type)) {
            $nullable = in_array('null', $type, true);
            $type = collect($type)->first(fn (string $t): bool => $t !== 'null') ?? 'string';
        }

        $builder = match ($type) {
            'string' => self::buildString($schema, $def),
            'integer' => self::buildInteger($schema, $def),
            'number' => self::buildNumber($schema, $def),
            'boolean' => $schema->boolean(),
            'array' => self::buildArray($schema, $def),
            default => $schema->string(),
        };

        if ($nullable) {
            $builder->nullable();
        }

        if (isset($def['description']) && is_string($def['description'])) {
            $builder->description($def['description']);
        }

        if (isset($def['enum']) && is_array($def['enum'])) {
            /** @var array<int, mixed> $enumValues */
            $enumValues = array_values($def['enum']);
            $builder->enum($enumValues);
        }

        return $builder;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private static function buildString(JsonSchema $schema, array $def): Type
    {
        $builder = $schema->string();

        if (isset($def['pattern']) && is_string($def['pattern'])) {
            $builder->pattern($def['pattern']);
        }

        if (isset($def['format']) && is_string($def['format'])) {
            $builder->format($def['format']);
        }

        return $builder;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private static function buildInteger(JsonSchema $schema, array $def): Type
    {
        $builder = $schema->integer();

        if (isset($def['minimum']) && is_numeric($def['minimum'])) {
            $builder->min((int) $def['minimum']);
        }

        if (isset($def['maximum']) && is_numeric($def['maximum'])) {
            $builder->max((int) $def['maximum']);
        }

        if (isset($def['multipleOf']) && is_numeric($def['multipleOf'])) {
            $builder->multipleOf((int) $def['multipleOf']);
        }

        return $builder;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private static function buildNumber(JsonSchema $schema, array $def): Type
    {
        $builder = $schema->number();

        if (isset($def['minimum']) && is_numeric($def['minimum'])) {
            $builder->min((float) $def['minimum']);
        }

        if (isset($def['maximum']) && is_numeric($def['maximum'])) {
            $builder->max((float) $def['maximum']);
        }

        if (isset($def['multipleOf']) && is_numeric($def['multipleOf'])) {
            $builder->multipleOf((float) $def['multipleOf']);
        }

        return $builder;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private static function buildArray(JsonSchema $schema, array $def): Type
    {
        $builder = $schema->array();

        if (isset($def['items']) && is_array($def['items'])) {
            /** @var array<string, mixed> $items */
            $items = $def['items'];
            $builder->items(self::buildField($schema, $items));
        }

        if (isset($def['minItems']) && is_numeric($def['minItems'])) {
            $builder->min((int) $def['minItems']);
        }

        if (isset($def['maxItems']) && is_numeric($def['maxItems'])) {
            $builder->max((int) $def['maxItems']);
        }

        return $builder;
    }
}
