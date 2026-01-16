<?php

namespace App\Filament\Resources;

use App\Enums\PayrollStatus;
use App\Filament\Resources\PayrollResource\Pages;
use App\Filament\Resources\PayrollResource\RelationManagers;
use App\Models\Payroll;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static string | \UnitEnum | null $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('Technician')
                    ->options(
                        User::where('role', 'Tech')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->required()
                    ->searchable()
                    ->columnSpan(1),

                Forms\Components\Select::make('status')
                    ->options(PayrollStatus::class)
                    ->required()
                    ->default(PayrollStatus::Draft)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('year')
                    ->numeric()
                    ->required()
                    ->minValue(2020)
                    ->maxValue(2099)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('week_number')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(53)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('gross_amount')
                    ->label('Gross Amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Calculated from linked tasks')
                    ->columnSpan(1),

                Forms\Components\TextInput::make('bonus_amount')
                    ->label('Bonus Amount')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->columnSpan(1),

                Forms\Components\TextInput::make('deductions_amount')
                    ->label('Calculated Deductions')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Calculated from loan installments')
                    ->columnSpan(1),

                Forms\Components\TextInput::make('deduction_override')
                    ->label('Deduction Override')
                    ->numeric()
                    ->prefix('$')
                    ->nullable()
                    ->helperText('Optional: Override calculated deductions')
                    ->columnSpan(1),

                Forms\Components\TextInput::make('net_pay')
                    ->label('Net Pay')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'font-bold text-lg'])
                    ->helperText('Gross + Bonus - Deductions'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Technician')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Gross')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bonus_amount')
                    ->label('Bonus')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deductions_amount')
                    ->label('Deductions')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_pay')
                    ->label('Net Pay')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PayrollStatus::class),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Technician')
                    ->options(
                        User::where('role', 'Tech')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('recalculate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Payroll $record) {
                        $record->recalculate();
                        \Filament\Notifications\Notification::make()
                            ->title('Payroll Recalculated')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Recalculate Payroll')
                    ->modalDescription('This will re-sum all linked tasks and loan installments.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PayrollItemsRelationManager::class,
            RelationManagers\LoanDeductionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
