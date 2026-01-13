<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\User;
use App\Filament\Resources\TechnicianResource\Pages;
use App\Filament\Resources\TechnicianResource\RelationManagers;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Schemas\Components\Grid as InfoGrid;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Filament\Schemas\Schema;

class TechnicianResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'technicians';

    protected static ?string $navigationLabel = 'Technicians';

    protected static ?string $modelLabel = 'Technician';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'HR & Team';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::Tech);
    }



    // ...

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfoSection::make('Identity')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Login Email')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('wire3_email')
                            ->email()
                            ->label('Wire3 Email')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->hintAction(
                                Action::make('call')
                                    ->icon('heroicon-m-phone')
                                    ->url(fn($state) => "tel:{$state}")
                                    ->openUrlInNewTab()
                            ),
                        Forms\Components\TextInput::make('job_title')
                            ->label('Job Title')
                            ->maxLength(255),
                    ]),
                InfoSection::make('Security')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create'),
                    ]),
                InfoSection::make('Status')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Account')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(function (User $record): string {
                        if (!$record->last_seen_at) return 'Offline';
                        return $record->last_seen_at->diffInMinutes(now()) > 5 ? 'Offline' : 'Online';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Online' => 'success',
                        'Offline' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->url(fn($state) => "tel:{$state}"),
                Tables\Columns\TextColumn::make('inventory_count')
                    ->label('Inventory Items')
                    ->counts('inventoryWallet'),
                Tables\Columns\TextColumn::make('tasks_today_count')
                    ->label('Tasks Today')
                    ->state(fn(User $record) => $record->tasks()->whereDate('scheduled_date', today())->count())
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                InfoSection::make('Current Week Live Status')
                    ->columnSpanFull()
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('current_week_earnings')
                                    ->label('Estimated Earnings (So Far)')
                                    ->money('USD')
                                    ->color('warning')
                                    ->helperText('Calculated based on completed tasks from start of week until now.'),
                                TextEntry::make('week_tasks_count')
                                    ->label('Tasks Count (This Week)')
                                    ->state(
                                        fn(User $record) => $record->tasks()
                                            ->where('status', \App\Enums\TaskStatus::Completed)
                                            ->where('completion_date', '>=', \Carbon\Carbon::now()->startOfWeek())
                                            ->count()
                                    ),
                            ]),
                    ]),
                InfoSection::make('Dashboard')
                    ->columnSpanFull()
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('balance')
                                    ->label('Total Unpaid Loans')
                                    ->state(fn(User $record) => $record->loans()->sum('amount_total')) // Assuming amount column
                                    ->color('danger')
                                    ->money('USD'),
                                TextEntry::make('performance')
                                    ->label('Tasks Completed (Month)')
                                    ->state(fn(User $record) => $record->tasks()->where('status', \App\Enums\TaskStatus::Completed)->whereMonth('completion_date', now()->month)->count()),
                                TextEntry::make('wallet_count')
                                    ->label('Items in Truck')
                                    ->state(fn(User $record) => $record->inventoryWallet()->sum('quantity')),
                            ]),
                    ]),
                InfoSection::make('Personal Details')
                    ->columnSpan(3) // Explicitly span 3 columns
                    ->schema([
                        TextEntry::make('phone')
                            ->url(fn($state) => "tel:{$state}"),
                        TextEntry::make('email'),
                        TextEntry::make('wire3_email'),
                        TextEntry::make('created_at')
                            ->label('Join Date')
                            ->date(),
                    ]),
                InfoSection::make('Location')
                    ->columnSpan(3) // Explicitly span 3 columns
                    ->schema([
                        Infolists\Components\ViewEntry::make('location_map')
                            ->view('filament.infolists.entries.map-widget')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InventoryWalletRelationManager::class,
            RelationManagers\TasksRelationManager::class,
            RelationManagers\LoansRelationManager::class,
            RelationManagers\PayrollsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTechnicians::route('/'),
            'create' => Pages\CreateTechnician::route('/create'),
            'view' => Pages\ViewTechnician::route('/{record}'),
            'edit' => Pages\EditTechnician::route('/{record}/edit'),
        ];
    }
}
