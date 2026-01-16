<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Customer;
use App\Models\OriginalTech;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('importTasks')
                ->label('Import Tasks')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Excel/CSV File')
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->maxSize(10240)
                        ->helperText('Upload an Excel (.xlsx, .xls) or CSV file. Max size: 10MB'),

                    Forms\Components\Select::make('original_tech_id')
                        ->label('Original Tech (From Wire3)')
                        ->options(OriginalTech::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->helperText('Select the technician code from Wire3 system'),

                    Forms\Components\Select::make('assigned_tech_id')
                        ->label('Assign To (Optional)')
                        ->options(User::where('role', 'Tech')->orderBy('name')->pluck('name', 'id'))
                        ->nullable()
                        ->searchable()
                        ->helperText('Leave empty for Dispatcher to assign later'),

                    Forms\Components\TextInput::make('schedule_date_column')
                        ->label('Date Column Name')
                        ->default('date')
                        ->required()
                        ->helperText('Column name in Excel that contains the scheduled date (e.g., "date", "scheduled_date")'),

                    Forms\Components\TextInput::make('cid_column')
                        ->label('CID Column Name')
                        ->default('cid')
                        ->required()
                        ->helperText('Column name for Wire3 Customer ID'),

                    Forms\Components\TextInput::make('name_column')
                        ->label('Customer Name Column')
                        ->default('name')
                        ->required(),

                    Forms\Components\TextInput::make('address_column')
                        ->label('Address Column')
                        ->default('address')
                        ->required(),

                    Forms\Components\TextInput::make('phone_column')
                        ->label('Phone Column')
                        ->default('phone')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = Storage::disk('local')->path($data['file']);
                    $rows = Excel::toArray([], $filePath)[0] ?? [];

                    if (empty($rows)) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('The file is empty or could not be read.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Get header row
                    $headers = array_shift($rows);
                    $headers = array_map('strtolower', $headers);

                    // Map column names
                    $dateColIndex = array_search(strtolower($data['schedule_date_column']), $headers);
                    $cidColIndex = array_search(strtolower($data['cid_column']), $headers);
                    $nameColIndex = array_search(strtolower($data['name_column']), $headers);
                    $addressColIndex = array_search(strtolower($data['address_column']), $headers);
                    $phoneColIndex = array_search(strtolower($data['phone_column']), $headers);

                    if ($dateColIndex === false || $cidColIndex === false) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Required columns not found in file.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $importBatchId = 'IMP-' . now()->format('YmdHis');
                    $tasksCreated = 0;
                    $tasksSkipped = 0;

                    foreach ($rows as $row) {
                        try {
                            $cid = $row[$cidColIndex] ?? null;
                            $scheduledDate = $row[$dateColIndex] ?? null;
                            $name = $row[$nameColIndex] ?? 'Unknown Customer';
                            $address = $row[$addressColIndex] ?? '';
                            $phone = $row[$phoneColIndex] ?? '';

                            if (!$cid || !$scheduledDate) {
                                $tasksSkipped++;
                                continue;
                            }

                            // Parse date
                            try {
                                $scheduledDate = \Carbon\Carbon::parse($scheduledDate)->format('Y-m-d');
                            } catch (\Exception $e) {
                                $tasksSkipped++;
                                continue;
                            }

                            // Duplicate check: Skip if task exists for same CID on same date
                            $existingTask = Task::whereHas('customer', function (Builder $query) use ($cid) {
                                $query->where('wire3_cid', $cid);
                            })
                                ->where('scheduled_date', $scheduledDate)
                                ->exists();

                            if ($existingTask) {
                                $tasksSkipped++;
                                continue;
                            }

                            // Find or create customer
                            $customer = Customer::firstOrCreate(
                                ['wire3_cid' => $cid],
                                [
                                    'name' => $name,
                                    'address' => $address,
                                    'phone' => $phone,
                                ]
                            );

                            // Create task
                            Task::create([
                                'customer_id' => $customer->id,
                                'original_tech_id' => $data['original_tech_id'],
                                'assigned_tech_id' => $data['assigned_tech_id'] ?? null,
                                'task_type' => TaskType::NewInstall, // Default
                                'status' => $data['assigned_tech_id'] ? TaskStatus::Assigned : TaskStatus::Pending,
                                'financial_status' => TaskFinancialStatus::Billable,
                                'scheduled_date' => $scheduledDate,
                                'import_batch_id' => $importBatchId,
                                'description' => "Imported from batch {$importBatchId}",
                            ]);

                            $tasksCreated++;
                        } catch (\Exception $e) {
                            $tasksSkipped++;
                            continue;
                        }
                    }

                    // Clean up uploaded file
                    Storage::disk('local')->delete($data['file']);

                    Notification::make()
                        ->title('Import Complete')
                        ->body("Successfully imported {$tasksCreated} tasks. Skipped {$tasksSkipped} duplicates/invalid rows.")
                        ->success()
                        ->send();
                })
                ->modalHeading('Import Tasks from Excel')
                ->modalDescription('Upload an Excel file to bulk import tasks. The system will automatically create customers and link tasks.')
                ->modalSubmitActionLabel('Import')
                ->modalWidth('2xl'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Tasks')
                ->icon('heroicon-o-rectangle-stack'),

            'dispatch' => Tab::make('Dispatch Board')
                ->icon('heroicon-o-clipboard-document-list')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', TaskStatus::Pending))
                ->badge(fn() => Task::where('status', TaskStatus::Pending)->count())
                ->badgeColor('danger'),

            'qc' => Tab::make('Quality Control')
                ->icon('heroicon-o-shield-check')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', TaskStatus::Completed))
                ->badge(fn() => Task::where('status', TaskStatus::Completed)->count())
                ->badgeColor('warning'),

            'done' => Tab::make('Done')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', TaskStatus::Approved))
                ->badge(fn() => Task::where('status', TaskStatus::Approved)->count())
                ->badgeColor('success'),
        ];
    }
}
