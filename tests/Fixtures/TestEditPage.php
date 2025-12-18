<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Filament\Resources\Pages\EditRecord;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;

class TestEditPage extends EditRecord
{
    protected static string $resource = TestResource::class;

    /**
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            AiGenerateAction::make(),
        ];
    }
}
