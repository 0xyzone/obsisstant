<?php

namespace App\Filament\Resources\ScreenCollections;

use App\Filament\Resources\ScreenCollections\Pages\CreateScreenCollection;
use App\Filament\Resources\ScreenCollections\Pages\EditScreenCollection;
use App\Filament\Resources\ScreenCollections\Pages\ListScreenCollections;
use App\Filament\Resources\ScreenCollections\Schemas\ScreenCollectionForm;
use App\Filament\Resources\ScreenCollections\Tables\ScreenCollectionsTable;
use App\Models\ScreenCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ScreenCollectionResource extends Resource
{
    protected static ?string $model = ScreenCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'OBS Studio';

    protected static ?string $navigationLabel = 'Screen Collections';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->ownedBy(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return ScreenCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScreenCollectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScreenCollections::route('/'),
            'create' => CreateScreenCollection::route('/create'),
            'edit' => EditScreenCollection::route('/{record}/edit'),
        ];
    }
}
