<?php

namespace App\Filament\Resources;

use App\Enums\TaskType;
use App\Filament\Resources\JobPriceResource\Pages;
use App\Models\JobPrice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class JobPriceResource extends Resource
{
    protected static ?string $model = JobPrice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('task_type')
                    ->options(TaskType::class)
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('company_price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                Forms\Components\TextInput::make('tech_price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tech_price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListJobPrices::route('/'),
            'create' => Pages\CreateJobPrice::route('/create'),
            'edit' => Pages\EditJobPrice::route('/{record}/edit'),
        ];
    }
}
