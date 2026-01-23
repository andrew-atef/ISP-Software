<?php

namespace App\Filament\Resources\Tasks\Schemas;


use App\Enums\InstallationType;
use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\JobPrice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Section 1: Customer Details
                Section::make('Customer Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('wire3_cid')
                            ->label('Wire3 CID')
                            ->required(),
                        TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->required(),
                        TextInput::make('customer_phone')
                            ->label('Phone Number')
                            ->tel(),
                        TextInput::make('customer_address')
                            ->label('Address')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // Section 2: Schedule & Assignment
                Section::make('Schedule & Assignment')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        DatePicker::make('scheduled_date')
                            ->default(now()->addDay())
                            ->required(),
                        TimePicker::make('time_slot_start')
                            ->seconds(false)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $end = $get('time_slot_end');
                                if ($state && $end && $state >= $end) {
                                    $set('time_slot_end', null);
                                }
                            }),
                        TimePicker::make('time_slot_end')
                            ->seconds(false)
                            ->rules([
                                fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                    $start = $get('time_slot_start');
                                    if ($start && $value && $value <= $start) {
                                        $fail('End time must be after start time.');
                                    }
                                },
                            ]),

                        Select::make('original_tech_id')
                            ->label('Original Tech (Wire3)')
                            ->relationship('originalTech', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('assigned_tech_id')
                            ->relationship('assignedTech', 'name', fn ($query) => $query->where('role', \App\Enums\UserRole::Tech))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set) =>
                                $state ? $set('status', TaskStatus::Assigned) : $set('status', TaskStatus::Pending)
                            ),

                        Select::make('task_type')
                            ->options(TaskType::class)
                            ->default(TaskType::NewInstall)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                self::updatePricing($get, $set);
                            }),

                        Select::make('status')
                            ->options(TaskStatus::class)
                            ->default(TaskStatus::Pending)
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ]),

                // Section 3: Financials
                Section::make('Financials')
                    ->columnSpanFull()
                    ->columns(3)
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->schema([
                        Select::make('financial_status')
                            ->options(TaskFinancialStatus::class)
                            ->default(TaskFinancialStatus::Billable)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                self::updatePricing($get, $set);
                            }),
                        TextInput::make('company_price')
                            ->label('Company Price (Wire3)')
                            ->numeric()
                            ->prefix('$')
                            ->default(fn () => JobPrice::where('task_type', TaskType::NewInstall)->first()?->company_price ?? 0)
                            ->required(),
                        TextInput::make('tech_price')
                            ->label('Tech Price')
                            ->numeric()
                            ->prefix('$')
                            ->default(fn () => JobPrice::where('task_type', TaskType::NewInstall)->first()?->tech_price ?? 0)
                            ->required(),
                    ]),

                // Section 4: Inventory Consumption (Tracked Items Only)
                Section::make('Inventory Consumption')
                    ->columnSpanFull()
                    ->description('Select major devices (ONTs, Eeros) installed during this task. (Tech reports via App only)')
                    ->hiddenOn('create')
                    ->schema([
                        // Installation Type - Field-reported data (Editable by Super Admin only)
                        Select::make('installation_type')
                            ->label('Installation Type')
                            ->helperText('Reported by technician via mobile app')
                            ->options(InstallationType::class)
                            ->disabled(fn () => ! auth()->user()->hasRole('super_admin'))
                            ->dehydrated()
                            ->hiddenOn('create'),

                        Repeater::make('inventory_consumption')
                            ->relationship('inventoryConsumptions')
                            ->disabled()
                            ->schema([
                                Select::make('inventory_item_id')
                                    ->label('Device')
                                    ->options(function () {
                                        return \App\Models\InventoryItem::where('is_tracked', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Device')
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function updatePricing($get, $set): void
    {
        $taskType = $get('task_type');
        $financialStatus = $get('financial_status');

        if (! $taskType) {
            return;
        }

        // Handle Enum vs String
        $typeValue = $taskType instanceof TaskType ? $taskType->value : $taskType;
        $financialStatusValue = $financialStatus instanceof TaskFinancialStatus ? $financialStatus->value : $financialStatus;

        // Fetch Base Prices from DB
        $jobPrice = \App\Models\JobPrice::where('task_type', $typeValue)->first();

        // Default to 0 if not found
        $baseCompanyPrice = $jobPrice ? $jobPrice->company_price : 0;
        $baseTechPrice = $jobPrice ? $jobPrice->tech_price : 0;

        // Apply Override Logic
        // If NotBillable -> Force company_price to 0 (Keep tech_price as Base).
        // Else -> Set company_price to Base.

        if ($financialStatusValue === TaskFinancialStatus::NotBillable->value) {
            $set('company_price', 0);
        } else {
            $set('company_price', $baseCompanyPrice);
        }

        $set('tech_price', $baseTechPrice);
    }
}
