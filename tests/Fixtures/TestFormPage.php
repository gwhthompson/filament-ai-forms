<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;

/**
 * Test fixture: Livewire component with AI-enabled form fields.
 *
 * Used for integration testing where components need proper form container context.
 */
class TestFormPage extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->aiSchema(enabled: true, description: 'The name'),
                TextInput::make('email')
                    ->email()
                    ->aiSchema(enabled: true),
                TextInput::make('count')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(100)
                    ->aiSchema(enabled: true),
                TextInput::make('optional_note')
                    ->nullable()
                    ->aiSchema(enabled: true, required: false),
                Checkbox::make('is_active')
                    ->aiSchema(enabled: true),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->form }}
        </div>
        HTML;
    }
}
