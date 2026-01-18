<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\UserRole;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'assignedTech']))
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
                    ->placeholder('Assign Tech')
                    ->icon(fn ($record) => $record->status === TaskStatus::Pending ? 'heroicon-m-user-plus' : 'heroicon-m-user')
                    ->color(fn ($record) => $record->status === TaskStatus::Pending ? 'primary' : 'gray')
                    ->weight(fn ($record) => $record->status === TaskStatus::Pending ? 'bold' : 'normal')
                    ->action(
                        Action::make('assignTech')
                            ->icon('heroicon-o-user-plus')
                            ->color('primary')
                            ->tooltip('Assign Technician')
                            ->visible(fn ($record) => $record->status === TaskStatus::Pending)
                            ->requiresConfirmation()
                            ->form([
                                Select::make('assigned_tech_id')
                                    ->label('Assign To')
                                    ->options(fn (): array => User::query()
                                        ->where('role', UserRole::Tech)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->required(),
                            ])
                            ->action(function ($record, array $data) {
                                $record->update([
                                    'assigned_tech_id' => $data['assigned_tech_id'] ?? null,
                                    'status' => TaskStatus::Assigned,
                                ]);
                            })
                    ),
                TextColumn::make('task_type')
                    ->badge()
                    ->color(fn(TaskType $state): string => match ($state) {
                        TaskType::NewInstall => 'success',
                        TaskType::DropBury => 'warning',
                        TaskType::ServiceCall => 'info',
                        TaskType::ServiceChange => 'gray',
                    }),
                TextColumn::make('installation_type')
                    ->label('Installation')
                    ->badge()
                    ->toggleable(),
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
                TextColumn::make('completion_date')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_offline_sync')
                    ->label('Offline')
                    ->boolean()
                    ->trueIcon('heroicon-s-wifi')
                    ->falseIcon('heroicon-o-wifi')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('financial_status')
                    ->badge()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
                TextColumn::make('tech_price')
                    ->label('Tech Pay')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
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
                ViewAction::make()
                    ->color('gray'),
                Action::make('approve')
                    ->label('Approve Job')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->tooltip('Approve & Bill')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === TaskStatus::Completed)
                    ->action(fn ($record) => $record->update(['status' => TaskStatus::Approved])),
                Action::make('returnForFix')
                    ->label('Return for Fix')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === TaskStatus::Completed)
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $reason = trim($data['reason'] ?? '');
                        $prefix = '[QC Return] ';
                        $timestamp = now()->toDateTimeString();
                        $desc = trim(($record->description ? ($record->description . "\n") : '') . $prefix . $reason . " ({$timestamp})");
                        $record->update([
                            'status' => TaskStatus::ReturnedForFix,
                            'description' => $desc,
                        ]);
                    }),
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
                            $records->each(function ($record) use ($data) {
                                $updateData = ['status' => $data['status']];
                                // Fix: Bulk Action Logic - Set completion_date if marking as Completed
                                if ($data['status'] === \App\Enums\TaskStatus::Completed->value && $record->completion_date === null) {
                                    $updateData['completion_date'] = now();
                                }
                                $record->update($updateData);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
