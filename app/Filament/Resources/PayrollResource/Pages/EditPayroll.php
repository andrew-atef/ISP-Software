<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('recalculate')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    $this->record->recalculate();
                    $this->fillForm();
                    \Filament\Notifications\Notification::make()
                        ->title('Payroll Recalculated')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Recalculate Payroll')
                ->modalDescription('This will re-sum all linked tasks and loan installments.'),
        ];
    }
}
