<?php

namespace App\Filament\Resources\JobPriceResource\Pages;

use App\Filament\Resources\JobPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobPrice extends EditRecord
{
    protected static string $resource = JobPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
