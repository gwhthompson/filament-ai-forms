<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Enums\ReasoningEffort;
use Gwhthompson\FilamentAiForms\Enums\ServiceTier;
use Gwhthompson\FilamentAiForms\Enums\Verbosity;

describe('ReasoningEffort', function (): void {
    it('has expected cases', function (): void {
        expect(ReasoningEffort::cases())
            ->toHaveCount(5)
            ->toContain(ReasoningEffort::None)
            ->toContain(ReasoningEffort::Minimal)
            ->toContain(ReasoningEffort::Low)
            ->toContain(ReasoningEffort::Medium)
            ->toContain(ReasoningEffort::High);
    });

    it('has correct string values', function (ReasoningEffort $case, string $expected): void {
        expect($case->value)->toBe($expected);
    })->with([
        'None' => [ReasoningEffort::None, 'none'],
        'Minimal' => [ReasoningEffort::Minimal, 'minimal'],
        'Low' => [ReasoningEffort::Low, 'low'],
        'Medium' => [ReasoningEffort::Medium, 'medium'],
        'High' => [ReasoningEffort::High, 'high'],
    ]);

    it('can be created from string', function (): void {
        expect(ReasoningEffort::from('high'))->toBe(ReasoningEffort::High)
            ->and(ReasoningEffort::from('minimal'))->toBe(ReasoningEffort::Minimal);
    });

    it('can try from invalid string', function (): void {
        expect(ReasoningEffort::tryFrom('invalid'))->toBeNull();
    });
});

describe('ServiceTier', function (): void {
    it('has expected cases', function (): void {
        expect(ServiceTier::cases())
            ->toHaveCount(5)
            ->toContain(ServiceTier::Auto)
            ->toContain(ServiceTier::Default)
            ->toContain(ServiceTier::Flex)
            ->toContain(ServiceTier::Scale)
            ->toContain(ServiceTier::Priority);
    });

    it('has correct string values', function (ServiceTier $case, string $expected): void {
        expect($case->value)->toBe($expected);
    })->with([
        'Auto' => [ServiceTier::Auto, 'auto'],
        'Default' => [ServiceTier::Default, 'default'],
        'Flex' => [ServiceTier::Flex, 'flex'],
        'Scale' => [ServiceTier::Scale, 'scale'],
        'Priority' => [ServiceTier::Priority, 'priority'],
    ]);

    it('can be created from string', function (): void {
        expect(ServiceTier::from('priority'))->toBe(ServiceTier::Priority)
            ->and(ServiceTier::from('auto'))->toBe(ServiceTier::Auto);
    });

    it('can try from invalid string', function (): void {
        expect(ServiceTier::tryFrom('unknown'))->toBeNull();
    });
});

describe('Verbosity', function (): void {
    it('has expected cases', function (): void {
        expect(Verbosity::cases())
            ->toHaveCount(3)
            ->toContain(Verbosity::Low)
            ->toContain(Verbosity::Medium)
            ->toContain(Verbosity::High);
    });

    it('has correct string values', function (Verbosity $case, string $expected): void {
        expect($case->value)->toBe($expected);
    })->with([
        'Low' => [Verbosity::Low, 'low'],
        'Medium' => [Verbosity::Medium, 'medium'],
        'High' => [Verbosity::High, 'high'],
    ]);

    it('can be created from string', function (): void {
        expect(Verbosity::from('medium'))->toBe(Verbosity::Medium)
            ->and(Verbosity::from('high'))->toBe(Verbosity::High);
    });

    it('can try from invalid string', function (): void {
        expect(Verbosity::tryFrom('extreme'))->toBeNull();
    });
});
