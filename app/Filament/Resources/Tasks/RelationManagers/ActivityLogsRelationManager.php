<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $title = 'Activity Log';
    protected static ?string $recordTitleAttribute = 'description';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Causer')
                    ->formatStateUsing(fn (Activity $record) => $record->causer?->name ?? 'System')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (Activity $record) => $record->description)
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('properties_old')
                    ->label('Old Value')
                    ->formatStateUsing(fn (Activity $record) => $this->formatChanges($record->changes()['old'] ?? []))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('properties_attributes')
                    ->label('New Value')
                    ->formatStateUsing(fn (Activity $record) => $this->formatChanges($record->changes()['attributes'] ?? []))
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No activity yet')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    private function formatChanges(array $changes): string
    {
        if (empty($changes)) {
            return '-';
        }

        return collect($changes)
            ->map(fn ($value, $key) => "$key: " . (is_scalar($value) ? (string) $value : json_encode($value)))
            ->implode("\n");
    }
}
