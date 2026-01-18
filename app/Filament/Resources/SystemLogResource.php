<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemLogResource\Pages\ListSystemLogs;
use App\Models\JobPrice;
use App\Models\Payroll;
use App\Models\Task;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class SystemLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'System Logs';
    protected static string|UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 99;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('super_admin') ?? false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Causer')
                    ->formatStateUsing(fn (Activity $record) => $record->causer?->name ?? 'System')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->formatStateUsing(fn (Activity $record) => self::formatSubject($record))
                    ->wrap()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (Activity $record) => $record->description)
                    ->searchable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('properties_old')
                    ->label('Old Value')
                    ->formatStateUsing(fn (Activity $record) => self::formatChanges($record->changes()['old'] ?? []))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('properties_attributes')
                    ->label('New Value')
                    ->formatStateUsing(fn (Activity $record) => self::formatChanges($record->changes()['attributes'] ?? []))
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Log')
                    ->options([
                        'task' => 'Tasks',
                        'payroll' => 'Payroll',
                        'job_price' => 'Job Prices',
                    ]),
                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No activity recorded')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSystemLogs::route('/'),
        ];
    }

    private static function formatSubject(Activity $record): string
    {
        if (! $record->subject) {
            return 'N/A';
        }

        return match ($record->subject_type) {
            Task::class => 'Task #' . $record->subject_id,
            Payroll::class => 'Payroll #' . $record->subject_id,
            JobPrice::class => 'Job Price ' . ($record->subject?->task_type?->value ?? '#' . $record->subject_id),
            default => class_basename($record->subject_type) . ' #' . $record->subject_id,
        };
    }

    private static function formatChanges(array $changes): string
    {
        if (empty($changes)) {
            return '-';
        }

        return collect($changes)
            ->map(fn ($value, $key) => "$key: " . (is_scalar($value) ? (string) $value : json_encode($value)))
            ->implode("\n");
    }
}
