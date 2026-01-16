<?php

namespace App\Filament\Resources;

use App\Enums\TaskType;
use App\Filament\Resources\JobPriceResource\Pages;
use App\Models\JobPrice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
                    ->options(function (?Model $record) {
                        // Get all task types that exist in database
                        // But if we're editing, exclude the current record from the count
                        $takenTypes = JobPrice::query()
                            ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                            ->pluck('task_type')
                            ->map(fn($v) => $v instanceof TaskType ? $v->value : $v)
                            ->toArray();

                        // Return only task types that are not taken
                        return collect(TaskType::cases())
                            ->filter(fn(TaskType $t) => !in_array($t->value, $takenTypes))
                            ->mapWithKeys(fn(TaskType $t) => [$t->value => $t->getLabel()])
                            ->toArray();
                    })
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn(string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('company_price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->default(0),
                Forms\Components\TextInput::make('tech_price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
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
            ->paginated(false)
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // DeleteBulkAction intentionally removed - pricing rules cannot be deleted
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

    /**
     * Prevent creation when all TaskType cases are already defined.
     *
     * This ensures the "Create" button only appears when there are
     * pricing rules still to be added.
     */
    public static function canCreate(): bool
    {
        $existingCount = JobPrice::query()->count();
        $enumCasesCount = count(TaskType::cases());

        return $existingCount < $enumCasesCount;
    }
}
