<?php

namespace App\Filament\Resources\OriginalTeches\Pages;

use App\Filament\Resources\OriginalTeches\OriginalTechResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOriginalTech extends EditRecord
{
    protected static string $resource = OriginalTechResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
