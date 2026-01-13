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
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->disabled(), // Generally immutable once created?
                Forms\Components\TextInput::make('week_number')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('year')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('gross_amount')
                    ->numeric()
                    ->prefix('$')
                    ->readOnly(),
                Forms\Components\TextInput::make('deductions_amount')
                    ->numeric()
                    ->prefix('$')
                    ->readOnly(),
                Forms\Components\TextInput::make('net_pay')
                    ->numeric()
                    ->prefix('$')
                    ->readOnly(),
                Forms\Components\Select::make('status')
                    ->options(PayrollStatus::class)
                    ->required(),
            ]);
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
