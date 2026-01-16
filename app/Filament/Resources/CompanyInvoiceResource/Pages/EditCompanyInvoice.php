<?php

namespace App\Filament\Resources\CompanyInvoiceResource\Pages;

use App\Filament\Resources\CompanyInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyInvoice extends EditRecord
{
    protected static string $resource = CompanyInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
