<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Filament\Forms\Components\DatePicker;
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
                // Section 1: Customer & Schedule
                Section::make('Customer & Schedule')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('scheduled_date')
                            ->required(),
                        TimePicker::make('time_slot_start')
                            ->seconds(false),
                        TimePicker::make('time_slot_end')
                            ->seconds(false),
                    ]),

                // Section 2: Assignment & Type
                Section::make('Assignment & Type')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('original_tech_id')
                            ->label('Original Tech (Wire3)')
                            ->relationship('originalTech', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('assigned_tech_id')
                            ->relationship('assignedTech', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('task_type')
                            ->options(TaskType::class)
                            ->required(),
                        Select::make('status')
                            ->options(TaskStatus::class)
                            ->default(TaskStatus::Pending)
                            ->required(),
                    ]),

                // Section 3: Financials
                Section::make('Financials')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('financial_status')
                            ->options(TaskFinancialStatus::class)
                            ->default(TaskFinancialStatus::NotBillable)
                            ->required(),
                        TextInput::make('company_price')
                            ->label('Company Price (Wire3)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00)
                            ->required(),
                        TextInput::make('tech_price')
                            ->label('Tech Price')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00)
                            ->required(),
                    ]),
            ]);
    }
}
