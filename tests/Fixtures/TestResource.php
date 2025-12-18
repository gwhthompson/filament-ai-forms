<?php

declare(strict_types=1);

namespace Gwhthompson\FilamentAiForms\Tests\Fixtures;

use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TestResource extends Resource
{
    protected static ?string $model = TestModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->aiSchema(description: 'The company or brand name'),
            Textarea::make('description')
                ->aiSchema(description: 'A marketing description'),
        ]);
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => TestListPage::route('/'),
            'create' => TestCreatePage::route('/create'),
            'edit' => TestEditPage::route('/{record}/edit'),
        ];
    }
}
