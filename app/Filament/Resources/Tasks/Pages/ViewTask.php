<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // =====================
            // QC ACTION A: Approve Job
            // =====================
            Action::make('approveJob')
                ->label('Approve Job')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Approve This Job?')
                ->modalDescription('This will mark the job as approved and add it to the billing queue.')
                ->modalSubmitActionLabel('Yes, Approve')
                ->visible(fn(Task $record): bool => $record->status === TaskStatus::Completed)
                ->action(function (Task $record): void {
                    $record->update([
                        'status' => TaskStatus::Approved,
                    ]);

                    Notification::make()
                        ->title('Job Approved')
                        ->body('Job approved and added to billing.')
                        ->success()
                        ->send();
                }),

            // =====================
            // QC ACTION B: Return for Fix
            // =====================
            Action::make('returnForFix')
                ->label('Return for Fix')
                ->color('danger')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn(Task $record): bool => $record->status === TaskStatus::Completed)
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Why are you returning this?')
                        ->placeholder('Describe the issue that needs to be fixed...')
                        ->required()
                        ->rows(4),
                ])
                ->modalHeading('Return Task for Fixes')
                ->modalDescription('The technician will be notified and the task will be reopened.')
                ->modalSubmitActionLabel('Return to Technician')
                ->action(function (Task $record, array $data): void {
                    // Update status
                    $record->update([
                        'status' => TaskStatus::ReturnedForFix,
                    ]);

                    // Append rejection reason to tech notes
                    $adminNote = "\n\n---\n**[ADMIN RETURN - " . now()->format('Y-m-d H:i') . "]:**\n" . $data['rejection_reason'];

                    if ($record->detail) {
                        $record->detail->update([
                            'tech_notes' => ($record->detail->tech_notes ?? '') . $adminNote,
                        ]);
                    }

                    // Send Database Notification to the assigned technician
                    if ($record->assignedTech) {
                        Notification::make()
                            ->title('Task Returned for Fixes')
                            ->body("Task #{$record->id} for customer {$record->customer?->name} has been returned. Reason: " . $data['rejection_reason'])
                            ->warning()
                            ->sendToDatabase($record->assignedTech);
                    }

                    // Show success notification to admin
                    Notification::make()
                        ->title('Task Returned')
                        ->body('Task returned to technician. They have been notified.')
                        ->warning()
                        ->send();
                }),

            // Standard Edit Action
            EditAction::make(),
        ];
    }
}
