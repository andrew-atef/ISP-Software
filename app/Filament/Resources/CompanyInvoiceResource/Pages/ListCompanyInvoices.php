<?php

namespace App\Filament\Resources\CompanyInvoiceResource\Pages;

use App\Filament\Resources\CompanyInvoiceResource;
use Filament\Actions;
use Filament\Forms\Get;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListCompanyInvoices extends ListRecords
{
    protected static string $resource = CompanyInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_invoice')
                ->label('Generate Invoice')
                ->form([
                    \Filament\Forms\Components\TextInput::make('year')
                        ->label('Year')
                        ->numeric()
                        ->default(now()->year)
                        ->required()
                        ->live(),
                    \Filament\Forms\Components\TextInput::make('week_number')
                        ->label('Week Number')
                        ->numeric()
                        ->default(now()->subWeek()->weekOfYear)
                        ->minValue(1)
                        ->maxValue(53)
                        ->required()
                        ->live(),
                    \Filament\Forms\Components\Placeholder::make('date_range_preview')
                        ->label('Date Range Preview')
                        ->content(function ($get) {
                            $year = $get('year');
                            $week = $get('week_number');
                            if (! $year || ! $week) {
                                return '-';
                            }
                            $start = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek(\Carbon\CarbonInterface::SUNDAY);
                            $end = $start->copy()->endOfWeek(\Carbon\CarbonInterface::SATURDAY);
                            return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
                        }),
                ])
                ->action(function (array $data) {
                    // CRITICAL: Wrap entire operation in transaction with row locking
                    // to prevent race condition (double-billing if two admins generate simultaneously)
                    DB::transaction(function () use ($data) {
                        $startDate = \Carbon\Carbon::now()->setISODate($data['year'], $data['week_number'])->startOfWeek(\Carbon\CarbonInterface::SUNDAY)->startOfDay();
                        $endDate = $startDate->copy()->endOfWeek(\Carbon\CarbonInterface::SATURDAY)->endOfDay();

                        // 1. Find Tasks with Row Locking
                        // lockForUpdate() ensures these rows cannot be modified until transaction commits
                        $tasks = \App\Models\Task::query()
                            ->where('status', \App\Enums\TaskStatus::Approved)
                            ->where('financial_status', \App\Enums\TaskFinancialStatus::Billable)
                            ->whereNull('company_invoice_id')
                            ->whereBetween('completion_date', [$startDate, $endDate])
                            ->lockForUpdate() // CRITICAL: Prevent other processes from modifying
                            ->get();

                        if ($tasks->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('No tasks found')
                                ->body('No billable, approved tasks found for Week ' . $data['week_number'] . ' of ' . $data['year'] . '.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 2. Create Invoice
                        $totalAmount = $tasks->sum('company_price');

                        $invoice = \App\Models\CompanyInvoice::create([
                            'period_start' => $startDate,
                            'period_end' => $endDate,
                            'week_number' => $data['week_number'],
                            'total_amount' => $totalAmount,
                            'status' => 'Draft',
                        ]);

                        // 3. Update Tasks
                        // This update is still protected by lockForUpdate()
                        \App\Models\Task::whereIn('id', $tasks->pluck('id'))->update([
                            'company_invoice_id' => $invoice->id,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Invoice Generated')
                            ->body('Generated invoice ' . $invoice->invoice_number . ' with ' . $tasks->count() . ' tasks')
                            ->success()
                            ->send();
                    });
                }),
        ];
    }
}
