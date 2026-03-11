<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Gwhthompson\FilamentAiForms\Actions\AiGenerateAction;
use Gwhthompson\FilamentAiForms\Tests\Concerns\ResetsFilamentState;

class TestEditPageWithHooks extends EditRecord
{
    use ResetsFilamentState;

    protected static string $resource = TestResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            AiGenerateAction::make()
                ->beforeGeneration(app('test.beforeGeneration'))
                ->afterGeneration(app('test.afterGeneration')),
        ];
    }
}
