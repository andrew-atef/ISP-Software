<?php

namespace App\Filament\Resources\TechnicianResource\RelationManagers;

use App\Enums\InventoryItemType;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryWalletRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryWallets';

    protected static ?string $title = 'Inventory';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only form - no manual editing allowed
                Forms\Components\Placeholder::make('item.name')
                    ->label('Item Name'),
                Forms\Components\Placeholder::make('quantity')
                    ->label('Quantity'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.name')
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn($record) => match (true) {
                        $record->quantity === 0 => 'danger',
                        $record->quantity < 5 => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('item.type')
                    ->label('Item Type')
                    ->options(InventoryItemType::class),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (< 5)')
                    ->query(fn($query) => $query->where('quantity', '<', 5)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn($query) => $query->where('quantity', 0)),
            ])
            ->headerActions([
                // No Create action - inventory is managed via Requests/Transactions
            ])
            ->actions([
                // No Edit/Delete actions - Read-only for audit trail integrity
                ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - Read-only
            ])
            ->defaultSort('item.name', 'asc')
            ->emptyStateHeading('No inventory items')
            ->emptyStateDescription('This technician has no items in inventory. Items are added via inventory requests and transactions.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }
}
