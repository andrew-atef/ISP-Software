<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // $this->record is available in EditRecord
        $task = $this->getRecord();

        if ($task->customer) {
            $data['wire3_cid'] = $task->customer->wire3_cid;
            $data['customer_name'] = $task->customer->name;
            $data['customer_phone'] = $task->customer->phone;
            $data['customer_address'] = $task->customer->address;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if customer exists by wire3_cid
        $existingCustomer = \App\Models\Customer::where('wire3_cid', $data['wire3_cid'])->first();

        if ($existingCustomer) {
            // Customer exists: Apply "Smart Update" logic
            // Compare form inputs with existing customer data and update if changed
            $updatedFields = [];

            if ($data['customer_name'] !== $existingCustomer->name) {
                $updatedFields['name'] = $data['customer_name'];
            }

            if (($data['customer_phone'] ?? null) !== $existingCustomer->phone) {
                $updatedFields['phone'] = $data['customer_phone'] ?? null;
            }

            if ($data['customer_address'] !== $existingCustomer->address) {
                $updatedFields['address'] = $data['customer_address'];
            }

            // Only update if there are actual changes
            if (!empty($updatedFields)) {
                $existingCustomer->update($updatedFields);
            }

            $customerId = $existingCustomer->id;
        } else {
            // New Customer: Create fresh record
            $customerData = [
                'wire3_cid' => $data['wire3_cid'],
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? null,
                'address' => $data['customer_address'],
            ];
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
