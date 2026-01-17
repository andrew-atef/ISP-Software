<?php

namespace App\Filament\Resources;

use App\Models\InventoryRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;

class InventoryRequestResource extends Resource
{
    protected static ?string $model = InventoryRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'Tech'))
                    ->label('Technician')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Request Notes')
                    ->columnSpanFull()
                    ->rows(3),

                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('inventory_item_id')
                            ->label('Item')
                            ->relationship('item', 'name')
                            ->options(fn () => \App\Models\InventoryItem::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('quantity_requested')
                            ->label('Quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->addActionLabel('Add Item')
                    ->collapsible(),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),

                Forms\Components\DateTimePicker::make('approved_at')
                    ->label('Approved At')
                    ->nullable()
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('approved_by')
                    ->relationship('approvedBy', 'name', fn ($q) => $q?->where('role', \App\Enums\UserRole::Admin))
                    ->label('Approved By (Admin)')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Technician')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('items_count')
                    ->label('Item Count')
                    ->counts('items'),
                TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->label('Requested On'),
                TextColumn::make('approved_at')
                    ->dateTime('M d, Y H:i')
                    ->label('Approved At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Technician')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve Request')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (InventoryRequest $record) => $record->status === 'pending')
                    ->action(function (InventoryRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Request Approved')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('receive')
                    ->label('Mark as Received')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->visible(fn (InventoryRequest $record) => $record->status === 'approved')
                    ->action(function (InventoryRequest $record) {
                        $record->update(['status' => 'received']);

                        // Add all requested quantities to tech's inventory wallet
                        foreach ($record->items as $item) {
                            $wallet = \App\Models\InventoryWallet::firstOrCreate(
                                ['user_id' => $record->user_id, 'inventory_item_id' => $item->inventory_item_id],
                                ['quantity' => 0]
                            );
                            $wallet->increment('quantity', $item->quantity_requested);

                            // Log transaction
                            \App\Models\InventoryTransaction::create([
                                'inventory_item_id' => $item->inventory_item_id,
                                'source_user_id' => null, // From stock/admin
                                'target_user_id' => $record->user_id,
                                'quantity' => $item->quantity_requested,
                                'type' => 'restock',
                                'notes' => "Inventory request #{$record->id} received",
                            ]);
                        }

                        Notification::make()
                            ->title('Items added to Technician Wallet')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\InventoryRequestResource\Pages\ListInventoryRequests::route('/'),
            'create' => \App\Filament\Resources\InventoryRequestResource\Pages\CreateInventoryRequest::route('/create'),
            'edit' => \App\Filament\Resources\InventoryRequestResource\Pages\EditInventoryRequest::route('/{record}/edit'),
        ];
    }
}
