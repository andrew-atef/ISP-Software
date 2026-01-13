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
        $task = $this->getRecord();

        if ($task->customer) {
            $task->customer->update([
                'wire3_cid' => $data['wire3_cid'],
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? $task->customer->phone,
                'address' => $data['customer_address'],
            ]);
        }

        // Cleanup fields that don't exist in tasks table
        unset($data['wire3_cid']);
        unset($data['customer_name']);
        unset($data['customer_phone']);
        unset($data['customer_address']);

        return $data;
    }
}
