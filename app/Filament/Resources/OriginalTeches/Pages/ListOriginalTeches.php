<?php

namespace App\Filament\Resources\OriginalTeches\Pages;

use App\Filament\Resources\OriginalTeches\OriginalTechResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOriginalTeches extends ListRecords
{
    protected static string $resource = OriginalTechResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
