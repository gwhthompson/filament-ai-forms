<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Simple Livewire fixture for testing schema components.
 * Based on awcodes/shout testing pattern.
 */
class Livewire extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function make(): static
    {
        return new static;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    public function render(): View
    {
        return view('livewire.form');
    }
}
