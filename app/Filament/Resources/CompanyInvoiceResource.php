<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyInvoiceResource\Pages;
use App\Filament\Resources\CompanyInvoiceResource\RelationManagers;
use App\Models\CompanyInvoice;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class CompanyInvoiceResource extends Resource
{
    protected static ?string $model = CompanyInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Forms\Components\TextInput::make('invoice_number')
                    ->disabled()
                    ->dehydrated(false) // Auto-generated
                    ->placeholder('Auto-generated')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('year')
                    ->label('Year')
                    ->numeric()
                    ->required()
                    ->default(now()->year)
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record) {
                        if ($record && $record->period_start) {
                            $component->state($record->period_start->year);
                        }
                    })
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updatePeriodDates($get, $set);
                    })
                    ->columnSpan(1),

                Forms\Components\TextInput::make('week_number')
                    ->label('Week Number')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(53)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updatePeriodDates($get, $set);
                    })
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('period_start')
                    ->label('Period Start')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('period_end')
                    ->label('Period End')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->columnSpan(1),

                Forms\Components\TextInput::make('total_amount')
                    ->prefix('$')
                    ->numeric()
                    ->readOnly()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'Sent' => 'Sent',
                        'Paid' => 'Paid',
                    ])
                    ->default('Draft')
                    ->required(),
            ]);
    }

    public static function updatePeriodDates(Get $get, Set $set): void
    {
        $year = $get('year');
        $week = $get('week_number');

        if (! $year || ! $week) {
            return;
        }

        $startDate = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek(CarbonInterface::SUNDAY);
        $endDate = $startDate->copy()->endOfWeek(CarbonInterface::SATURDAY);

        $set('period_start', $startDate->format('Y-m-d'));
        $set('period_end', $endDate->format('Y-m-d'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('period_start')->date()->sortable(),
                Tables\Columns\TextColumn::make('period_end')->date()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->money()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Sent' => 'warning',
                        'Paid' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (CompanyInvoice $record) {
                        return response()->streamDownload(function () use ($record) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.wire3', ['invoice' => $record]);
                            echo $pdf->stream();
                        }, 'invoice-' . $record->invoice_number . '.pdf');
                    }),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyInvoices::route('/'),
            'edit' => Pages\EditCompanyInvoice::route('/{record}/edit'),
        ];
    }

    /**
     * Disable manual invoice creation.
     *
     * Invoices must ONLY be generated via the "Generate Invoice" action
     * to ensure financial accuracy and prevent bypassing the business logic.
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
