<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Filament\Resources\Pages\EditRecord;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;
use Gwhthompson\FilamentAiForms\Tests\Concerns\ResetsFilamentState;

class TestEditPage extends EditRecord
{
    use ResetsFilamentState;

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
