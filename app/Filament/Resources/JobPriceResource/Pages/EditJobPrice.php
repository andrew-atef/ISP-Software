<?php

namespace App\Filament\Resources\JobPriceResource\Pages;

use App\Filament\Resources\JobPriceResource;
use Filament\Resources\Pages\EditRecord;

class EditJobPrice extends EditRecord
{
    protected static string $resource = JobPriceResource::class;

    /**
     * No delete actions allowed.
     *
     * JobPrice records cannot be deleted because:
     * 1. They define core pricing logic for the ERP
     * 2. Deletion causes calculation errors (Zero Pricing)
     * 3. Records are seeded and managed through the seeder only
     * 4. Users should only modify prices, not remove pricing rules
     *
     * To modify pricing: Use form to edit company_price/tech_price
     * To reset to default: Re-run the JobPriceSeeder
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
