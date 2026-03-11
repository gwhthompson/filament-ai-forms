<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Agents\FormGenerationAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\StringType;

covers(FormGenerationAgent::class);

describe('FormGenerationAgent', function (): void {
    describe('instructions()', function (): void {
        it('returns the default instructions', function (): void {
            $agent = new FormGenerationAgent;

            expect($agent->instructions())->toBe('You are an AI assistant generating structured data.');
        });

        it('returns custom instructions', function (): void {
            $agent = new FormGenerationAgent(
                systemInstructions: 'You are a product description generator.',
            );

            expect($agent->instructions())->toBe('You are a product description generator.');
        });
    });

    describe('schema()', function (): void {
        it('delegates to JsonSchemaConverter and returns correct keys', function (): void {
            $rawSchema = [
                'properties' => [
                    'name' => ['type' => 'string', 'description' => 'Product name'],
                    'quantity' => ['type' => 'integer', 'minimum' => 0],
                ],
                'required' => ['name'],
            ];

            $agent = new FormGenerationAgent(rawSchema: $rawSchema);
            $schema = new JsonSchemaTypeFactory;

            $result = $agent->schema($schema);

            expect($result)->toHaveCount(2)
                ->and($result)->toHaveKeys(['name', 'quantity'])
                ->and($result['name'])->toBeInstanceOf(StringType::class)
                ->and($result['quantity'])->toBeInstanceOf(IntegerType::class);
        });

        it('returns empty array when no schema provided', function (): void {
            $agent = new FormGenerationAgent;
            $schema = new JsonSchemaTypeFactory;

            $result = $agent->schema($schema);

            expect($result)->toBe([]);
        });
    });
});
