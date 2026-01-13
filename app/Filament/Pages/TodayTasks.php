<?php

namespace App\Filament\Pages;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Task;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TodayTasks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboard';

    protected static ?string $title = 'Today\'s Tasks';

    protected string $view = 'filament.pages.today-tasks';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->whereDate('scheduled_date', Carbon::today())
            )
            ->columns([
                TextColumn::make('customer.wire3_cid')
                    ->label('Customer ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn(Task $record): string => $record->customer?->address ?? '-'),
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
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn(Task $record) => TaskResource::getUrl('view', ['record' => $record])),
            ])
            ->poll('30s');
    }
}
