<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Data\AiGenerationResult;

describe('AiGenerationResult', function (): void {
    describe('construction', function (): void {
        it('creates with required fields', function (): void {
            $result = new AiGenerationResult(
                data: ['title' => 'Test', 'description' => 'A description'],
                duration: 1.5,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 2,
            );

            expect($result->data)->toBe(['title' => 'Test', 'description' => 'A description'])
                ->and($result->duration)->toBe(1.5)
                ->and($result->model)->toBe('gpt-4.1-mini')
                ->and($result->fieldsGenerated)->toBe(2)
                ->and($result->logPath)->toBeNull()
                ->and($result->schema)->toBeNull()
                ->and($result->systemPrompt)->toBeNull()
                ->and($result->userPrompt)->toBeNull();
        });

        it('creates with all optional fields', function (): void {
            $result = new AiGenerationResult(
                data: ['field' => 'value'],
                duration: 2.3,
                model: 'gpt-4.1',
                fieldsGenerated: 1,
                logPath: '/logs/test.md',
                schema: ['type' => 'object'],
                systemPrompt: 'You are helpful',
                userPrompt: 'Generate content',
            );

            expect($result->logPath)->toBe('/logs/test.md')
                ->and($result->schema)->toBe(['type' => 'object'])
                ->and($result->systemPrompt)->toBe('You are helpful')
                ->and($result->userPrompt)->toBe('Generate content');
        });

        it('creates from array using Data::from()', function (): void {
            // Spatie Data uses property names as-is (camelCase)
            $result = AiGenerationResult::from([
                'data' => ['name' => 'Test'],
                'duration' => 0.5,
                'model' => 'gpt-4.1-mini',
                'fieldsGenerated' => 1,
            ]);

            expect($result)->toBeInstanceOf(AiGenerationResult::class)
                ->and($result->data)->toBe(['name' => 'Test'])
                ->and($result->duration)->toBe(0.5)
                ->and($result->fieldsGenerated)->toBe(1);
        });
    });

    describe('nonNullFieldsCount()', function (): void {
        it('counts all non-null fields', function (): void {
            $result = new AiGenerationResult(
                data: [
                    'title' => 'Test',
                    'description' => 'Description',
                    'summary' => 'Summary',
                ],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 3,
            );

            expect($result->nonNullFieldsCount())->toBe(3);
        });

        it('excludes null fields from count', function (): void {
            $result = new AiGenerationResult(
                data: [
                    'title' => 'Test',
                    'description' => null,
                    'summary' => 'Summary',
                    'notes' => null,
                ],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 4,
            );

            expect($result->nonNullFieldsCount())->toBe(2);
        });

        it('returns zero for all null data', function (): void {
            $result = new AiGenerationResult(
                data: [
                    'field1' => null,
                    'field2' => null,
                ],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 2,
            );

            expect($result->nonNullFieldsCount())->toBe(0);
        });

        it('returns zero for empty data', function (): void {
            $result = new AiGenerationResult(
                data: [],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 0,
            );

            expect($result->nonNullFieldsCount())->toBe(0);
        });

        it('counts empty string as non-null', function (): void {
            $result = new AiGenerationResult(
                data: [
                    'title' => '',
                    'description' => null,
                ],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 2,
            );

            expect($result->nonNullFieldsCount())->toBe(1);
        });

        it('counts zero as non-null', function (): void {
            $result = new AiGenerationResult(
                data: [
                    'count' => 0,
                    'total' => null,
                ],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 2,
            );

            expect($result->nonNullFieldsCount())->toBe(1);
        });

        it('counts false as non-null', function (): void {
            $result = new AiGenerationResult(
                data: [
                    'active' => false,
                    'enabled' => null,
                ],
                duration: 1.0,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 2,
            );

            expect($result->nonNullFieldsCount())->toBe(1);
        });
    });

    describe('toArray()', function (): void {
        it('serializes to array', function (): void {
            $result = new AiGenerationResult(
                data: ['title' => 'Test'],
                duration: 1.5,
                model: 'gpt-4.1-mini',
                fieldsGenerated: 1,
            );

            $array = $result->toArray();

            expect($array)->toBeArray()
                ->and($array)->toHaveKey('data', ['title' => 'Test'])
                ->and($array)->toHaveKey('duration', 1.5)
                ->and($array)->toHaveKey('model', 'gpt-4.1-mini')
                // camelCase property names are preserved (no MapOutputName on this class)
                ->and($array)->toHaveKey('fieldsGenerated', 1);
        });
    });
});
