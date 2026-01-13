<?php

namespace App\Filament\Resources\TechnicianResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoansRelationManager extends RelationManager
{
    protected static string $relationship = 'loans';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('amount_total')
                    ->label('Total Amount')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $total = (float) $get('amount_total');
                        $count = (int) $get('installments_count');
                        if ($count > 0) {
                            $set('installment_amount', number_format($total / $count, 2, '.', ''));
                        }
                    }),
                Forms\Components\TextInput::make('installments_count')
                    ->label('Installments Count')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $total = (float) $get('amount_total');
                        $count = (int) $get('installments_count');
                        if ($count > 0) {
                            $set('installment_amount', number_format($total / $count, 2, '.', ''));
                        }
                    }),
                Forms\Components\TextInput::make('installment_amount')
                    ->label('Installment Amount')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->readOnly()
                    ->dehydrated(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('amount_total')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('installments_count')
                    ->label('Installments'),
                Tables\Columns\TextColumn::make('installment_amount')
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
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
