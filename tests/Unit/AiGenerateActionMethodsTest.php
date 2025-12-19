<?php

declare(strict_types=1);

use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;

covers(AiGenerateAction::class);

describe('formatValue via closure', function (): void {
    it('formats empty string with italic placeholder', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', '');

        expect($result)->toContain('Empty')
            ->and($result)->toContain('italic')
            ->and($result)->toContain('text-gray-400');
    });

    it('formats null value with italic placeholder', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', null);

        expect($result)->toContain('Empty');
    });

    it('formats empty array with italic placeholder', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', []);

        expect($result)->toContain('Empty');
    });

    it('formats array values as badges', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', ['tag1', 'tag2', 'tag3']);

        expect($result)->toContain('rounded-full')
            ->and($result)->toContain('tag1')
            ->and($result)->toContain('tag2')
            ->and($result)->toContain('tag3')
            ->and($result)->toContain('bg-primary-100');
    });

    it('escapes HTML in array values', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', ['<script>alert("xss")</script>']);

        expect($result)->toContain('&lt;script&gt;')
            ->and($result)->not->toContain('<script>');
    });

    it('formats scalar values in whitespace-preserving div', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', 'Hello World');

        expect($result)->toContain('Hello World')
            ->and($result)->toContain('whitespace-pre-wrap');
    });

    it('escapes HTML in scalar values', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', '<script>alert("xss")</script>');

        expect($result)->toContain('&lt;script&gt;')
            ->and($result)->not->toContain('<script>');
    });

    it('handles boolean false as empty', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', false);

        expect($result)->toContain('Empty');
    });

    it('handles zero as non-empty', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', 0);

        expect($result)->toContain('Empty');
    });

    it('formats integer values correctly', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', 42);

        expect($result)->toContain('42')
            ->and($result)->toContain('whitespace-pre-wrap');
    });

    it('formats multiline text with preserved whitespace', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'formatValue', "Line 1\nLine 2");

        expect($result)->toContain("Line 1\nLine 2")
            ->and($result)->toContain('whitespace-pre-wrap');
    });
});

describe('isEditPage via closure', function (): void {
    it('returns false when no livewire context', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'isEditPage');

        expect($result)->toBeFalse();
    });
});

describe('shouldBeVisible via closure', function (): void {
    it('returns false when not on edit page', function (): void {
        $action = AiGenerateAction::make();
        $result = callProtected($action, 'shouldBeVisible');

        expect($result)->toBeFalse();
    });
});

describe('extractComponents with null livewire', function (): void {
    it('returns empty array when getLivewire returns null', function (): void {
        $action = AiGenerateAction::make();
        // Action not mounted on Livewire - getLivewire() returns null
        $result = callProtected($action, 'extractComponents');

        expect($result)->toBe([]);
    });
});

describe('getExistingData with null livewire', function (): void {
    it('returns empty array when getLivewire returns null', function (): void {
        $action = AiGenerateAction::make();
        // Action not mounted on Livewire - getLivewire() returns null
        $result = callProtected($action, 'getExistingData');

        expect($result)->toBe([]);
    });
});

describe('handleAction with null livewire', function (): void {
    it('returns null when getLivewire returns null', function (): void {
        $action = AiGenerateAction::make();
        // Action not mounted on Livewire - getLivewire() returns null
        $result = callProtected($action, 'handleAction', [
            'generated_data' => '{"name":"Test"}',
            'selected_fields' => '["name"]',
        ]);

        expect($result)->toBeNull();
    });
});
