<?php

namespace App\Filament\Resources\TechnicianResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;

class PayrollsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrolls';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Period')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('week_number')
                            ->required()
                            ->numeric()
                            ->live(debounce: 500)
                            ->afterStateHydrated(function (Get $get, Set $set, $state, $record) {
                                // For RelationManager, $record might be the *owner* record (Technician) if viewed in context?
                                // Actually, in a Repeater or Table Action form, we need the *item* record.
                                // But here it's a RelationManager `form()`.
                                // In Filament v3 RelationManager, `form()` defines the schema for Create/Edit actions.
                                // So $record injected into closure is the Payroll record (nullable).
                                // $this->getOwnerRecord() is the Technician.
                                self::calculatePayroll($get, $set, $this->getOwnerRecord()->id, $record?->id);
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, $state, $record) {
                                self::calculatePayroll($get, $set, $this->getOwnerRecord()->id, $record?->id);
                            }),
                        Forms\Components\TextInput::make('year')
                            ->required()
                            ->numeric()
                            ->default(now()->year)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set, $state, $record) {
                                self::calculatePayroll($get, $set, $this->getOwnerRecord()->id, $record?->id);
                            }),
                    ]),

                Section::make('Earnings Breakdown')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('tasks_total')
                            ->label('Tasks Total')
                            ->disabled()
                            ->dehydrated(false)
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('bonus_amount')
                            ->label('Bonus / Adjustment (+)')
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => $state ?? 0)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                self::updateCalculations($get, $set);
                            }),

                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Final Gross Amount')
                            ->disabled()
                            ->dehydrated() // Save this to DB
                            ->numeric()
                            ->prefix('$'),
                    ]),

                Section::make('Deductions')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('system_calculated_deduction')
                            ->label('Scheduled Loan Deduction')
                            ->disabled()
                            ->dehydrated(false)
                            ->numeric()
                            ->prefix('$')
                            ->hint('Based on active loans due this week'),

                        Forms\Components\TextInput::make('deduction_override')
                            ->label('Loan Deduction Override (-)')
                            ->helperText('Enter a value here to override the scheduled deduction (e.g., to pay off the full loan).')
                            ->numeric()
                            ->nullable()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                self::updateCalculations($get, $set);
                            }),
                        
                        // Hidden field to store the actual used deduction amount
                        Forms\Components\Hidden::make('deductions_amount')
                            ->dehydrated(),
                    ]),

                Section::make('Final')
                    ->schema([
                        Forms\Components\TextInput::make('net_pay')
                            ->label('Net Pay')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->prefix('$')
                            ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: bold;']),
                    ]),
            ]);
    }

    protected static function calculatePayroll(Get $get, Set $set, $technicianId, $currentPayrollId = null): void
    {
        $week = $get('week_number');
        $year = $get('year');

        if (! $week || ! $year) {
            return;
        }

        // 1. Calculate Tasks Total
        try {
            $start = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek(\Carbon\Carbon::SUNDAY);
            $end = $start->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);

            $tasksTotal = \App\Models\Task::where('assigned_tech_id', $technicianId)
                ->where('status', \App\Enums\TaskStatus::Approved)
                ->whereBetween('completion_date', [$start, $end])
                ->sum('tech_price');
            
            $set('tasks_total', $tasksTotal);

            // 2. Calculate System Deductions
            $installments = \App\Models\LoanInstallment::whereHas('loan', function ($q) use ($technicianId) {
                $q->where('user_id', $technicianId);
            })
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
                ->where(function ($q) use ($currentPayrollId) {
                    $q->whereNull('payroll_id');
                    if ($currentPayrollId) {
                        $q->orWhere('payroll_id', $currentPayrollId);
                    }
                })
                ->get();
            
            $systemDeductions = $installments->sum('amount');
            $set('system_calculated_deduction', $systemDeductions);

            self::updateCalculations($get, $set);

        } catch (\Exception $e) {
            // Invalid date/week
        }
    }

    protected static function updateCalculations(Get $get, Set $set): void
    {
        $tasksTotal = (float) $get('tasks_total');
        $bonus = (float) $get('bonus_amount');
        
        $gross = $tasksTotal + $bonus;
        $set('gross_amount', $gross);

        $systemDeductions = (float) $get('system_calculated_deduction');
        $override = $get('deduction_override');
        
        // logic: if override is not null and not empty string, use it. Otherwise use system.
        $finalDeduction = ($override !== null && $override !== '') ? (float) $override : $systemDeductions;
        
        $set('deductions_amount', $finalDeduction);
        $set('net_pay', $gross - $finalDeduction);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week'),
                Tables\Columns\TextColumn::make('year')
                    ->label('Year'),
                Tables\Columns\TextColumn::make('net_pay')
                    ->label('Net Pay')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
