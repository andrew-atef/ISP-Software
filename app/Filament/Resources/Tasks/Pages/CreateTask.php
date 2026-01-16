<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $customerData = [
            'wire3_cid' => $data['wire3_cid'],
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'] ?? null,
            'address' => $data['customer_address'],
        ];

        // Check if customer exists by wire3_cid
        $existingCustomer = \App\Models\Customer::where('wire3_cid', $data['wire3_cid'])->first();

        if ($existingCustomer) {
            // Customer exists: Use their ID, DO NOT overwrite their data
            $customerId = $existingCustomer->id;
        } else {
            // New Customer: Create them
            $newCustomer = \App\Models\Customer::create($customerData);
            $customerId = $newCustomer->id;
        }

        $data['customer_id'] = $customerId;

        // Cleanup fields that don't exist in tasks table
        unset($data['wire3_cid']);
        unset($data['customer_name']);
        unset($data['customer_phone']);
        unset($data['customer_address']);

        return $data;
    }
}
