<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Gwhthompson\FilamentAiForms\Actions\AiChatAction;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Test Livewire component for integration testing AI actions.
 *
 * This component provides a minimal form with AI-enabled fields
 * to test AiGenerateAction and AiChatAction functionality.
 */
class TestFormComponent extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $data = [];

    public ?string $aiChatContent = null;

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->aiSchema(
                        enabled: true,
                        description: 'The name field',
                        required: true,
                    )
                    ->suffixAction(AiChatAction::make()),
                Textarea::make('description')
                    ->label('Description')
                    ->aiSchema(
                        enabled: true,
                        description: 'A description field',
                        required: false,
                    ),
            ])
            ->statePath('data');
    }

    /**
     * AI Generate action for testing.
     */
    public function aiGenerateAction(): AiGenerateAction
    {
        return AiGenerateAction::make();
    }

    /**
     * Get the default testing schema name.
     */
    public function getDefaultTestingSchemaName(): string
    {
        return 'form';
    }

    public function render(): View
    {
        return view('filament-ai-forms::tests.test-form');
    }
}
