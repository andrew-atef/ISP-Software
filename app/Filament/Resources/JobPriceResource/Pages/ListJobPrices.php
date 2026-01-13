<?php

namespace App\Filament\Resources\JobPriceResource\Pages;

use App\Filament\Resources\JobPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobPrices extends ListRecords
{
    protected static string $resource = JobPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
