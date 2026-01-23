<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Collection;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        // =====================
                        // SECTION 1: Job Info (Col Span 1)
                        // =====================
                        Section::make('Job Info')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                TextEntry::make('customer.wire3_cid')
                                    ->label('Wire3 CID')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('customer.name')
                                    ->label('Customer Name')
                                    ->weight('bold'),
                                TextEntry::make('customer.address')
                                    ->label('Address')
                                    ->icon('heroicon-o-map-pin')
                                    ->suffixAction(
                                        Action::make('openMaps')
                                            ->icon('heroicon-o-arrow-top-right-on-square')
                                            ->url(fn(Task $record): ?string => $record->customer?->lat && $record->customer?->lng
                                                ? "https://www.google.com/maps?q={$record->customer->lat},{$record->customer->lng}"
                                                : null)
                                            ->openUrlInNewTab()
                                            ->visible(fn(Task $record): bool => (bool) ($record->customer?->lat && $record->customer?->lng))
                                    ),
                                TextEntry::make('customer.phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                                TextEntry::make('assignedTech.name')
                                    ->label('Assigned Technician')
                                    ->icon('heroicon-o-user')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->placeholder('Unassigned')
                                    ->badge()
                                    ->color(fn($state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('scheduled_date')
                                    ->label('Scheduled Date')
                                    ->date()
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn(TaskStatus $state): string => match ($state) {
                                        TaskStatus::Completed, TaskStatus::Approved => 'success',
                                        TaskStatus::ReturnedForFix, TaskStatus::Cancelled => 'danger',
                                        TaskStatus::Pending, TaskStatus::Assigned => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('task_type')
                                    ->badge(),
                                TextEntry::make('installation_type')
                                    ->label('Installation Type')
                                    ->badge(),
                                TextEntry::make('financial_status')
                                    ->badge()
                                    ->color(fn($state): string => $state?->value === 'billable' ? 'success' : 'gray')
                                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
                            ]),

                        // =====================
                        // SECTION 2: Technical Data (Col Span 2)
                        // =====================
                        Section::make('Technical Data')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('installation_type')
                                    ->label('Installation Type')
                                    ->badge()
                                    ->placeholder('Not yet reported'),
                                TextEntry::make('detail.ont_serial')
                                    ->label('ONT')
                                    ->placeholder('Not scanned')
                                    ->copyable()
                                    ->icon('heroicon-o-qr-code'),
                                TextEntry::make('detail.eero_serial_1')
                                    ->label('Eero Main')
                                    ->placeholder('Not scanned')
                                    ->copyable(),
                                TextEntry::make('detail.eero_serial_2')
                                    ->label('Eero #2')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('detail.eero_serial_3')
                                    ->label('Eero #3')
                                    ->placeholder('-')
                                    ->copyable(),
                                IconEntry::make('detail.drop_bury_status')
                                    ->label('Bury Done?')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                                IconEntry::make('detail.sidewalk_bore_status')
                                    ->label('Sidewalk Bore Done?')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                                TextEntry::make('detail.start_time_actual')
                                    ->label('Start Time')
                                    ->dateTime(),
                                TextEntry::make('detail.end_time_actual')
                                    ->label('End Time')
                                    ->dateTime(),
                                TextEntry::make('detail.tech_notes')
                                    ->label('Technician Notes')
                                    ->markdown()
                                    ->placeholder('No notes provided.')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // =====================
                // SECTION 3: Photo Gallery (Full Width, Grouped by Type)
                // =====================
                Section::make('Photo Gallery')
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->schema([
                        // Work Photos
                        Section::make('Work Photos')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->description('Installation and site photos')
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(2)
                                    ->schema(fn ($record) => self::getPhotoEntries($record, 'work'))
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => $record->media()->where('type', 'work')->count() === 0),

                        // Bury Photos
                        Section::make('Bury Photos')
                            ->icon('heroicon-o-arrow-down')
                            ->description('Cable burial photos')
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(2)
                                    ->schema(fn ($record) => self::getPhotoEntries($record, 'bury'))
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => $record->media()->where('type', 'bury')->count() === 0),

                        // Bore Photos
                        Section::make('Bore Photos')
                            ->icon('heroicon-o-arrow-down')
                            ->description('Sidewalk bore photos')
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(2)
                                    ->schema(fn ($record) => self::getPhotoEntries($record, 'bore'))
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => $record->media()->where('type', 'bore')->count() === 0),

                        // General Photos
                        Section::make('General Photos')
                            ->icon('heroicon-o-camera')
                            ->description('Other site photos')
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(2)
                                    ->schema(fn ($record) => self::getPhotoEntries($record, 'general'))
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(fn ($record) => $record->media()->where('type', 'general')->count() === 0),
                    ]),

                // =====================
                // SECTION 4: Metadata (Collapsed)
                // =====================
                Section::make('Task Metadata')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('originalTech.name')
                            ->label('Original Tech'),
                        TextEntry::make('import_batch_id')
                            ->label('Import Batch'),
                        IconEntry::make('is_offline_sync')
                            ->label('Offline Sync')
                            ->boolean()
                            ->trueIcon('heroicon-s-wifi')
                            ->trueColor('warning'),
                        TextEntry::make('company_price')
                            ->label('Company Price')
                            ->money('USD')
                            ->visible(fn () => auth()->user()->hasRole('super_admin')),
                        TextEntry::make('tech_price')
                            ->label('Tech Price')
                            ->money('USD')
                            ->visible(fn () => auth()->user()->hasRole('super_admin')),
                        TextEntry::make('completion_date')
                            ->label('Completed At')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime(),
                    ]),
            ]);
    }

    private static function getPhotoEntries(Task $record, string $type): array
    {
        $photos = $record->media()->where('type', $type)->get();

        if ($photos->isEmpty()) {
            return [];
        }

        $entries = [];
        foreach ($photos as $photo) {
            // Use watermarked photo if watermark_data exists, otherwise use original
            $photoUrl = $photo->watermark_data
                ? route('photos.watermarked', $photo->id)
                : asset('storage/' . $photo->file_path);

            $entries[] = ImageEntry::make('photo_' . $photo->id)
                ->label($photo->taken_at?->format('M d, Y H:i') ?? 'Photo')
                ->getStateUsing(fn () => $photo->file_path)
                ->disk('public')
                ->columnSpan(1)
                ->height('auto')
                ->extraImgAttributes([
                    'style' => 'width: 100%; height: auto; object-fit: cover; cursor: pointer;',
                    'data-glightbox' => 'gallery=' . $type,
                    'data-gallery' => $type,
                    'href' => $photoUrl,
                ]);
        }

        return $entries;
    }
}
