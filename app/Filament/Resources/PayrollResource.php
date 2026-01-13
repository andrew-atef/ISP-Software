<?php

namespace App\Filament\Resources;

use App\Enums\PayrollStatus;
use App\Filament\Resources\PayrollResource\Pages;
use App\Models\Payroll;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Technician & Period')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null) // Disabled on edit
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state, ?Payroll $record) {
                                self::calculatePayroll($get, $set, $state, $record?->id);
                            }),
                        
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('week_number')
                                    ->required()
                                    ->numeric()
                                    ->live(debounce: 500)
                                    ->afterStateHydrated(function (Get $get, Set $set, $record) {
                                        $userId = $get('user_id') ?? $record?->user_id;
                                        if ($userId) {
                                            self::calculatePayroll($get, $set, $userId, $record?->id);
                                        }
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set, $state, ?Payroll $record) {
                                        self::calculatePayroll($get, $set, $get('user_id'), $record?->id);
                                    }),
                                Forms\Components\TextInput::make('year')
                                    ->required()
                                    ->numeric()
                                    ->default(now()->year)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state, ?Payroll $record) {
                                        self::calculatePayroll($get, $set, $get('user_id'), $record?->id);
                                    }),
                            ]),
                        
                        Forms\Components\Select::make('status')
                            ->options(PayrollStatus::class)
                            ->required()
                            ->default(PayrollStatus::Draft),
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
                            ->helperText('Enter a value here to override the scheduled deduction.')
                            ->numeric()
                            ->nullable()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                self::updateCalculations($get, $set);
                            }),
                        
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

    protected static function calculatePayroll(Get $get, Set $set, $userId, $currentPayrollId = null): void
    {
        $week = $get('week_number');
        $year = $get('year');

        if (! $week || ! $year || ! $userId) {
            return;
        }

        // 1. Calculate Tasks Total
        try {
            $start = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek(\Carbon\Carbon::SUNDAY);
            $end = $start->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);

            $tasksTotal = \App\Models\Task::where('assigned_tech_id', $userId)
                ->where('status', \App\Enums\TaskStatus::Approved)
                ->whereBetween('completion_date', [$start, $end])
                ->sum('tech_price');
            
            $set('tasks_total', $tasksTotal);

            // 2. Calculate System Deductions
            $installments = \App\Models\LoanInstallment::whereHas('loan', function ($q) use ($userId) {
                $q->where('user_id', $userId);
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
        
        $finalDeduction = ($override !== null && $override !== '') ? (float) $override : $systemDeductions;
        
        $set('deductions_amount', $finalDeduction);
        $set('net_pay', $gross - $finalDeduction);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Technician')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period')
                    ->label('Period')
                    ->state(fn(Payroll $record) => "W{$record->week_number} - {$record->year}")
                    ->sortable(['year', 'week_number']),
                Tables\Columns\TextColumn::make('net_pay')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PayrollStatus::class),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Action::make('recalculate')
                    ->label('Recalculate')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->visible(fn(Payroll $record) => $record->status === PayrollStatus::Draft)
                    ->action(function (Payroll $record) {
                        try {
                            $record->recalculate();
                            Notification::make()
                                ->title('Payroll updated')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error during recalculation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('approve')
                    ->label('Approve & Pay')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Payroll $record) => $record->status === PayrollStatus::Draft)
                    ->action(function (Payroll $record) {
                        $record->update(['status' => PayrollStatus::Paid]);
                        
                        // Mark associated loan installments as paid
                        \App\Models\LoanInstallment::whereHas('loan', function ($q) use ($record) {
                            $q->where('user_id', $record->user_id);
                        })
                        ->whereBetween('due_date', [
                            \Carbon\Carbon::now()->setISODate($record->year, $record->week_number)->startOfWeek(\Carbon\Carbon::SUNDAY),
                            \Carbon\Carbon::now()->setISODate($record->year, $record->week_number)->endOfWeek(\Carbon\Carbon::SATURDAY)
                        ])
                        ->update(['is_paid' => true, 'payroll_id' => $record->id]);

                        Notification::make()
                            ->title('Payroll approved and marked as Paid')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
        ];
    }
}
