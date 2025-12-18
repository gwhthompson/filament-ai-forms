<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Data\AiFieldMetadata;

describe('AiFieldMetadata', function (): void {
    describe('construction', function (): void {
        it('creates with required fields only', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'email',
                label: 'Email Address',
            );

            expect($metadata->name)->toBe('email')
                ->and($metadata->label)->toBe('Email Address')
                ->and($metadata->description)->toBeNull()
                ->and($metadata->prompt)->toBeNull()
                ->and($metadata->examples)->toBe([])
                ->and($metadata->options)->toBeNull();
        });

        it('creates with all optional fields', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'country',
                label: 'Country',
                description: 'Select a country',
                prompt: 'Extract the country mentioned',
                examples: ['United Kingdom', 'United States'],
                options: ['GB', 'US', 'CA', 'AU'],
            );

            expect($metadata->name)->toBe('country')
                ->and($metadata->label)->toBe('Country')
                ->and($metadata->description)->toBe('Select a country')
                ->and($metadata->prompt)->toBe('Extract the country mentioned')
                ->and($metadata->examples)->toBe(['United Kingdom', 'United States'])
                ->and($metadata->options)->toBe(['GB', 'US', 'CA', 'AU']);
        });

        it('creates from array using Data::from()', function (): void {
            // Spatie Data works with property names as-is (camelCase)
            $metadata = AiFieldMetadata::from([
                'name' => 'title',
                'label' => 'Title',
                'description' => 'The document title',
            ]);

            expect($metadata)->toBeInstanceOf(AiFieldMetadata::class)
                ->and($metadata->name)->toBe('title')
                ->and($metadata->label)->toBe('Title')
                ->and($metadata->description)->toBe('The document title');
        });
    });

    describe('hasOptions()', function (): void {
        it('returns false when options is null', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'text',
                label: 'Text',
            );

            expect($metadata->hasOptions())->toBeFalse();
        });

        it('returns false when options is empty array', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'text',
                label: 'Text',
                options: [],
            );

            expect($metadata->hasOptions())->toBeFalse();
        });

        it('returns true when options has values', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'status',
                label: 'Status',
                options: ['active', 'inactive'],
            );

            expect($metadata->hasOptions())->toBeTrue();
        });
    });

    describe('getEffectivePrompt()', function (): void {
        it('returns prompt when prompt is set', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'field',
                label: 'Field',
                description: 'A description',
                prompt: 'A specific prompt',
            );

            expect($metadata->getEffectivePrompt())->toBe('A specific prompt');
        });

        it('falls back to description when prompt is null', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'field',
                label: 'Field',
                description: 'A description',
            );

            expect($metadata->getEffectivePrompt())->toBe('A description');
        });

        it('returns null when both prompt and description are null', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'field',
                label: 'Field',
            );

            expect($metadata->getEffectivePrompt())->toBeNull();
        });
    });

    describe('toArray()', function (): void {
        it('serializes to array preserving property names', function (): void {
            $metadata = new AiFieldMetadata(
                name: 'email',
                label: 'Email',
                description: 'User email',
            );

            $array = $metadata->toArray();

            // AiFieldMetadata doesn't have MapOutputName, so keys stay camelCase
            expect($array)->toBeArray()
                ->and($array['name'])->toBe('email')
                ->and($array['label'])->toBe('Email')
                ->and($array['description'])->toBe('User email');
        });
    });
});
