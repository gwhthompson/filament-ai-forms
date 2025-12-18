<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Filament\Resources\Pages\CreateRecord;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;

class TestCreatePage extends CreateRecord
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
