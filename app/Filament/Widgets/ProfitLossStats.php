<?php

namespace App\Filament\Widgets;

use App\Enums\PayrollStatus;
use App\Models\CompanyInvoice;
use App\Models\Payroll;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitLossStats extends StatsOverviewWidget
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        [$start, $end] = $this->getDateRange();

        $revenue = (float) CompanyInvoice::query()
            ->where('status', '!=', 'Draft')
            ->whereBetween('period_start', [$start, $end])
            ->sum('total_amount');

        $payroll = (float) Payroll::query()
            ->where('status', PayrollStatus::Paid)
            ->whereBetween('created_at', [$start, $end])
            ->sum('net_pay');

        $profit = $revenue - $payroll;
        $margin = $revenue > 0.0 ? round(($profit / $revenue) * 100, 2) : 0.0;

        return [
            Stat::make('Total Revenue', '$' . number_format($revenue, 2))
                ->description('Invoices (non-draft)')
                ->color('success'),

            Stat::make('Total Payroll', '$' . number_format($payroll, 2))
                ->description('Paid payrolls')
                ->color('danger'),

            Stat::make('Net Profit', '$' . number_format($profit, 2))
                ->description('Margin: ' . number_format($margin, 2) . '%')
                ->color($profit >= 0 ? 'primary' : 'danger'),
        ];
    }

    /**
     * Normalize incoming date strings to Carbon instances and ensure boundaries are inclusive.
     */
    protected function getDateRange(): array
    {
        $start = $this->startDate
            ? Carbon::parse($this->startDate)->startOfDay()
            : now()->startOfMonth();

        $end = $this->endDate
            ? Carbon::parse($this->endDate)->endOfDay()
            : now()->endOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
