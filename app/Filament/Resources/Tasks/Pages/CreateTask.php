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
            'phone' => $data['customer_phone'] ?? null, // specific to this form, model says nullable usually?
            'address' => $data['customer_address'],
        ];

        // Find or create customer
        // Logic: Check if exists by wire3_cid. If yes, update. If no, create.
        
        $customer = \App\Models\Customer::where('wire3_cid', $data['wire3_cid'])->first();

        if ($customer) {
            $customer->update([
                'name' => $customerData['name'],
                'phone' => $customerData['phone'] ?? $customer->phone,
                'address' => $customerData['address'],
            ]);
        } else {
            $customer = \App\Models\Customer::create($customerData);
        }

        $data['customer_id'] = $customer->id;

        // Cleanup fields that don't exist in tasks table
        unset($data['wire3_cid']);
        unset($data['customer_name']);
        unset($data['customer_phone']);
        unset($data['customer_address']);

        return $data;
    }
}
