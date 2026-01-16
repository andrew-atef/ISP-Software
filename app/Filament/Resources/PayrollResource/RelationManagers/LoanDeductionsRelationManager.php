<?php

namespace App\Filament\Resources\PayrollResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoanDeductionsRelationManager extends RelationManager
{
    protected static string $relationship = 'loanInstallments';
    protected static ?string $title = 'Loan Deductions';
    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('loan.user.name')
                    ->label('Technician')
                    ->searchable(),

                Tables\Columns\TextColumn::make('loan.description')
                    ->label('Loan Description')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Installment Amount')
                    ->money('USD')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('USD')),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create/attach - installments are linked via wizard
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - prevent accidental unlinking
            ])
            ->defaultSort('due_date', 'asc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}
