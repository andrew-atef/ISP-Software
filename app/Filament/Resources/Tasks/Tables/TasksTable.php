<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.wire3_cid')
                    ->label('Wire3 CID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn($record): string => $record->customer?->address ?? '-'),
                TextColumn::make('originalTech.name')
                    ->label('Original Tech')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('assignedTech.name')
                    ->label('Technician')
                    ->searchable(),
                TextColumn::make('task_type')
                    ->badge()
                    ->color(fn(TaskType $state): string => match ($state) {
                        TaskType::NewInstall => 'success',
                        TaskType::DropBury => 'warning',
                        TaskType::ServiceCall => 'info',
                        TaskType::ServiceChange => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(TaskStatus $state): string => match ($state) {
                        TaskStatus::Completed, TaskStatus::Approved => 'success',
                        TaskStatus::Cancelled => 'danger',
                        TaskStatus::Pending, TaskStatus::Assigned => 'warning',
                        TaskStatus::Started, TaskStatus::Paused => 'info',
                        TaskStatus::ReturnedForFix => 'danger',
                    }),
                TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_offline_sync')
                    ->label('Offline')
                    ->boolean()
                    ->trueIcon('heroicon-s-wifi')
                    ->falseIcon('heroicon-o-wifi')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('financial_status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TaskStatus::class),
                SelectFilter::make('task_type')
                    ->options(TaskType::class),
                SelectFilter::make('assigned_tech_id')
                    ->label('Technician')
                    ->options(fn(): array => User::query()
                        ->where('role', 'tech')
                        ->pluck('name', 'id')
                        ->toArray()),
                Filter::make('scheduled_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('scheduled_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('scheduled_date', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('changeStatus')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->options(TaskStatus::class)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn($record) => $record->update(['status' => $data['status']]));
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
