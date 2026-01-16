<?php

namespace App\Filament\Widgets;

use App\Enums\PayrollStatus;
use App\Models\CompanyInvoice;
use App\Models\Payroll;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class ProfitLossTrendChart extends ChartWidget
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    protected ?string $heading = 'Revenue vs Payroll (Trend)';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        [$start, $end] = $this->getDateRange();

        $invoiceMap = CompanyInvoice::query()
            ->selectRaw('DATE(period_start) as date, SUM(total_amount) as total')
            ->where('status', '!=', 'Draft')
            ->whereBetween('period_start', [$start, $end])
            ->groupBy('date')
            ->pluck('total', 'date');

        $payrollMap = Payroll::query()
            ->selectRaw('DATE(created_at) as date, SUM(net_pay) as total')
            ->where('status', PayrollStatus::Paid)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $revenueData = [];
        $payrollData = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $revenueData[] = (float) ($invoiceMap[$key] ?? 0);
            $payrollData[] = (float) ($payrollMap[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenueData,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Payroll',
                    'data' => $payrollData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

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
