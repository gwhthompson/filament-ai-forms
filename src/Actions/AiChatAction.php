<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component as LivewireComponent;
use Override;

/**
 * Filament action for AI-powered field refinement via chat interface.
 *
 * Opens a modal with a streaming chat interface where users can refine
 * individual field content through conversation with AI.
 *
 * Usage:
 * ```php
 * Textarea::make('description')
 *     ->suffixAction(
 *         AiChatAction::make()
 *             ->systemPrompt('You are a copywriter...')
 *             ->initialPrompt('Help me improve this description')
 *     )
 * ```
 */
class AiChatAction extends Action
{
    protected string|Closure|null $systemPromptProvider = null;

    protected string|Closure|null $initialPromptProvider = null;

    protected string|Closure|null $contextPromptProvider = null;

    public static function getDefaultName(): ?string
    {
        return 'refineWithAi';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Refine with AI')
            ->icon(Heroicon::Sparkles)
            ->color('primary')
            ->modalHeading('AI Assistant')
            ->modalWidth(Width::ExtraLarge)
            ->modalSubmitActionLabel('Apply to Form')
            ->modalCancelActionLabel('Close')
            ->mountUsing(function (LivewireComponent $livewire, Component $component, $state): void {
                // Store the current field content for the modal
                // @phpstan-ignore-next-line Property access on Livewire component is dynamic
                $livewire->aiChatContent = $state ?? '';
            })
            ->modalContent(function (LivewireComponent $livewire, Component $component, Model $record): View {
                $recordKey = $record->getKey();
                $keyString = is_scalar($recordKey) ? (string) $recordKey : '';
                $identifier = $component->getStatePath().'.'.$keyString;
                // @phpstan-ignore-next-line Property access on Livewire component is dynamic
                $currentContent = $livewire->aiChatContent ?? '';
                $initialPrompt = $this->getInitialPrompt();
                $systemPrompt = $this->getSystemPrompt();
                $contextPrompt = $this->getContextPrompt();

                /** @var view-string $viewName */
                $viewName = 'filament-ai-forms::actions.ai-chat-modal';

                return view($viewName, [
                    'currentContent' => $currentContent,
                    'initialPrompt' => $initialPrompt,
                    'systemPrompt' => $systemPrompt,
                    'contextPrompt' => $contextPrompt,
                    'identifier' => $identifier,
                ]);
            })
            ->action(function (Set $set, Component $component, LivewireComponent $livewire): void {
                // Get the latest content from the embedded AI chat component
                // The content is stored in the parent Livewire component's aiChatContent property
                // @phpstan-ignore-next-line Property access on Livewire component is dynamic
                $rawContent = $livewire->aiChatContent ?? '';
                $content = is_string($rawContent) ? $rawContent : '';

                logger()->info('AiChatAction applying content', [
                    'content_length' => strlen($content),
                    'content_preview' => substr($content, 0, 100),
                    'component_path' => $component->getStatePath(),
                    'livewire_class' => $livewire::class,
                ]);

                if ($content === '') {
                    logger()->warning('AiChatAction: Content is empty, not applying');

                    return;
                }

                $set($component, $content);
                logger()->info('AiChatAction: Content applied successfully');
            });
    }

    /** Configure the system prompt for AI. */
    public function systemPrompt(string|Closure $provider): static
    {
        $this->systemPromptProvider = $provider;

        return $this;
    }

    /** Configure the initial prompt to seed the chat. */
    public function initialPrompt(string|Closure $provider): static
    {
        $this->initialPromptProvider = $provider;

        return $this;
    }

    /** Configure additional context to include in prompts. */
    public function contextPrompt(string|Closure $provider): static
    {
        $this->contextPromptProvider = $provider;

        return $this;
    }

    /** Get the resolved system prompt. */
    public function getSystemPrompt(): string
    {
        if ($this->systemPromptProvider === null) {
            return 'You are a helpful AI assistant.';
        }

        $result = $this->evaluate($this->systemPromptProvider);

        return is_string($result) ? $result : 'You are a helpful AI assistant.';
    }

    /** Get the resolved initial prompt. */
    public function getInitialPrompt(): string
    {
        if ($this->initialPromptProvider === null) {
            return '';
        }

        $result = $this->evaluate($this->initialPromptProvider);

        return is_string($result) ? $result : '';
    }

    /** Get the resolved context prompt. */
    public function getContextPrompt(): string
    {
        if ($this->contextPromptProvider === null) {
            return '';
        }

        $result = $this->evaluate($this->contextPromptProvider);

        return is_string($result) ? $result : '';
    }
}
