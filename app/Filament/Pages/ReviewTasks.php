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
use Filament\Actions\Action as TableAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Textarea;

class ReviewTasks extends Page implements HasTable
{
    use InteractsWithTable;

        public static function canAccess(): bool
        {
            return auth()->user()->can('View:ReviewTasks');
        }

    protected static ?string $title = 'Review Completed Tasks';

    protected string $view = 'filament.pages.review-tasks';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where('status', TaskStatus::Completed)
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
                    ->badge(),
                TextColumn::make('tech_price')
                    ->money('USD')
                    ->label('Price')
                    ->sortable(),
                TextColumn::make('completion_date')
                    ->date()
                    ->label('Completed On')
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn(Task $record) => TaskResource::getUrl('view', ['record' => $record])),
                TableAction::make('approve')
                    ->label('Approve Job')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Task $record) {
                        $record->update(['status' => TaskStatus::Approved]);
                        Notification::make()
                            ->title('Task Approved')
                            ->success()
                            ->send();
                    }),
                TableAction::make('return')
                    ->label('Return for Fix')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason for Return')
                            ->required(),
                    ])
                    ->action(function (Task $record, array $data) {
                        // Ideally store the reason somewhere, maybe a note?
                        // For now just changing status.
                        $record->update(['status' => TaskStatus::ReturnedForFix]);
                        Notification::make()
                            ->title('Returned for Fix')
                            ->body('Reason: ' . $data['reason'])
                            ->warning()
                            ->send();
                    }),
            ])
            ->poll('30s');
    }
}
