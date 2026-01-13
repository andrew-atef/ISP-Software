<?php

namespace App\Filament\Resources\OriginalTeches;

use App\Filament\Resources\OriginalTeches\Pages\CreateOriginalTech;
use App\Filament\Resources\OriginalTeches\Pages\EditOriginalTech;
use App\Filament\Resources\OriginalTeches\Pages\ListOriginalTeches;
use App\Filament\Resources\OriginalTeches\Schemas\OriginalTechForm;
use App\Filament\Resources\OriginalTeches\Tables\OriginalTechesTable;
use App\Models\OriginalTech;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OriginalTechResource extends Resource
{
    protected static ?string $model = OriginalTech::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return OriginalTechForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OriginalTechesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOriginalTeches::route('/'),
            'create' => CreateOriginalTech::route('/create'),
            'edit' => EditOriginalTech::route('/{record}/edit'),
        ];
    }
}
