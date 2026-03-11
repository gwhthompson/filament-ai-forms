<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Support\JsonSchemaConverter;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\StringType;

covers(JsonSchemaConverter::class);

beforeEach(function (): void {
    $this->schema = new JsonSchemaTypeFactory;
});

describe('convert()', function (): void {
    it('returns empty result for empty properties', function (): void {
        $result = JsonSchemaConverter::convert(['properties' => []], $this->schema);

        expect($result)->toBe([]);
    });

    it('returns empty result when properties key is missing', function (): void {
        $result = JsonSchemaConverter::convert([], $this->schema);

        expect($result)->toBe([]);
    });

    it('marks required fields', function (): void {
        $rawSchema = [
            'properties' => [
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result)->toHaveCount(2)
            ->and($result['name']->toArray())->toHaveKey('type', 'string')
            ->and($result['email']->toArray())->toHaveKey('type', 'string');

        // name should be required, email should not
        $nameArray = $result['name']->toArray();
        $emailArray = $result['email']->toArray();

        // The 'required' property is stripped from toArray() by the serializer,
        // but we can verify via reflection that it was set
        $nameRequired = (fn () => $this->required)->call($result['name']);
        $emailRequired = (fn () => $this->required)->call($result['email']);

        expect($nameRequired)->toBeTrue()
            ->and($emailRequired)->toBeNull();
    });
});

describe('buildField() via convert()', function (): void {
    it('builds a string field with description', function (): void {
        $rawSchema = [
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'The user name'],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['name'])->toBeInstanceOf(StringType::class)
            ->and($result['name']->toArray())
            ->toHaveKey('type', 'string')
            ->toHaveKey('description', 'The user name');
    });

    it('builds a boolean field', function (): void {
        $rawSchema = [
            'properties' => [
                'active' => ['type' => 'boolean'],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['active'])->toBeInstanceOf(BooleanType::class)
            ->and($result['active']->toArray())->toHaveKey('type', 'boolean');
    });

    it('defaults unknown type to string', function (): void {
        $rawSchema = [
            'properties' => [
                'custom' => ['type' => 'custom_unknown_type'],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['custom'])->toBeInstanceOf(StringType::class)
            ->and($result['custom']->toArray())->toHaveKey('type', 'string');
    });

    it('handles nullable unions', function (): void {
        $rawSchema = [
            'properties' => [
                'bio' => ['type' => ['string', 'null']],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['bio'])->toBeInstanceOf(StringType::class)
            ->and($result['bio']->toArray())->toHaveKey('type', ['string', 'null']);
    });

    it('applies enum values', function (): void {
        $rawSchema = [
            'properties' => [
                'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'pending']],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['status'])->toBeInstanceOf(StringType::class)
            ->and($result['status']->toArray())
            ->toHaveKey('type', 'string')
            ->toHaveKey('enum', ['active', 'inactive', 'pending']);
    });
});

describe('buildString()', function (): void {
    it('applies pattern constraint', function (): void {
        $rawSchema = [
            'properties' => [
                'code' => ['type' => 'string', 'pattern' => '^[A-Z]{3}$'],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['code'])->toBeInstanceOf(StringType::class)
            ->and($result['code']->toArray())
            ->toHaveKey('pattern', '^[A-Z]{3}$');
    });

    it('applies format constraint', function (): void {
        $rawSchema = [
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['email'])->toBeInstanceOf(StringType::class)
            ->and($result['email']->toArray())
            ->toHaveKey('format', 'email');
    });
});

describe('buildInteger()', function (): void {
    it('applies min and max constraints', function (): void {
        $rawSchema = [
            'properties' => [
                'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 150],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['age'])->toBeInstanceOf(IntegerType::class)
            ->and($result['age']->toArray())
            ->toHaveKey('minimum', 0)
            ->toHaveKey('maximum', 150);
    });

    it('applies multipleOf constraint as integer', function (): void {
        $rawSchema = [
            'properties' => [
                'quantity' => ['type' => 'integer', 'multipleOf' => 5],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['quantity'])->toBeInstanceOf(IntegerType::class)
            ->and($result['quantity']->toArray())
            ->toHaveKey('multipleOf', 5);
    });
});

describe('buildNumber()', function (): void {
    it('applies min and max constraints with float casting', function (): void {
        $rawSchema = [
            'properties' => [
                'price' => ['type' => 'number', 'minimum' => 0.01, 'maximum' => 9999.99],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['price'])->toBeInstanceOf(NumberType::class)
            ->and($result['price']->toArray())
            ->toHaveKey('minimum', 0.01)
            ->toHaveKey('maximum', 9999.99);
    });

    it('applies multipleOf constraint with float casting', function (): void {
        $rawSchema = [
            'properties' => [
                'weight' => ['type' => 'number', 'multipleOf' => 0.5],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['weight'])->toBeInstanceOf(NumberType::class)
            ->and($result['weight']->toArray())
            ->toHaveKey('multipleOf', 0.5);
    });

    it('casts integer values to float', function (): void {
        $rawSchema = [
            'properties' => [
                'score' => ['type' => 'number', 'minimum' => 1, 'maximum' => 10],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['score'])->toBeInstanceOf(NumberType::class);

        // Verify float casting via reflection
        $minimum = (fn () => $this->minimum)->call($result['score']);
        $maximum = (fn () => $this->maximum)->call($result['score']);

        expect($minimum)->toBe(1.0)
            ->and($maximum)->toBe(10.0);
    });
});

describe('buildArray()', function (): void {
    it('handles typed items', function (): void {
        $rawSchema = [
            'properties' => [
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['tags'])->toBeInstanceOf(ArrayType::class);

        $serialized = $result['tags']->toArray();
        expect($serialized)->toHaveKey('type', 'array')
            ->and($serialized['items'])->toHaveKey('type', 'string');
    });

    it('applies minItems and maxItems constraints', function (): void {
        $rawSchema = [
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'minItems' => 1,
                    'maxItems' => 10,
                ],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['items'])->toBeInstanceOf(ArrayType::class);

        $serialized = $result['items']->toArray();
        expect($serialized)
            ->toHaveKey('minItems', 1)
            ->toHaveKey('maxItems', 10);
    });

    it('handles nested array items (array of arrays)', function (): void {
        $rawSchema = [
            'properties' => [
                'matrix' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['matrix'])->toBeInstanceOf(ArrayType::class);

        $serialized = $result['matrix']->toArray();
        expect($serialized['items'])->toHaveKey('type', 'array')
            ->and($serialized['items']['items'])->toHaveKey('type', 'integer');
    });

    it('handles array without items definition', function (): void {
        $rawSchema = [
            'properties' => [
                'data' => ['type' => 'array'],
            ],
        ];

        $result = JsonSchemaConverter::convert($rawSchema, $this->schema);

        expect($result['data'])->toBeInstanceOf(ArrayType::class)
            ->and($result['data']->toArray())->toHaveKey('type', 'array');
    });
});
