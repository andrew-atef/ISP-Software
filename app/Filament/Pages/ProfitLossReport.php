<?php

namespace App\Filament\Pages;

use App\Enums\PayrollStatus;
use App\Filament\Widgets\ProfitLossStats;
use App\Filament\Widgets\ProfitLossTrendChart;
use App\Models\CompanyInvoice;
use App\Models\Payroll;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class ProfitLossReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string | \UnitEnum | null $navigationGroup = 'Financial';

    protected static ?string $navigationLabel = 'Profit & Loss';

    protected static ?string $title = 'Profit & Loss Report';

    protected string $view = 'filament.pages.profit-loss-report';

    public ?array $data = [];

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function mount(): void
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $this->startDate = $start;
        $this->endDate = $end;

        $this->form->fill([
            'startDate' => $start,
            'endDate' => $end,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('startDate')
                ->label('Start Date')
                ->native(false)
                ->default(now()->startOfMonth())
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->filter())
                ->required()
                ->columnSpan(1),

            Forms\Components\DatePicker::make('endDate')
                ->label('End Date')
                ->native(false)
                ->default(now()->endOfMonth())
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->filter())
                ->required()
                ->columnSpan(1),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormColumns(): int
    {
        return 2;
    }

    public function filter(): void
    {
        $state = $this->form->getState();

        $start = $state['startDate'] ?? now()->startOfMonth()->toDateString();
        $end = $state['endDate'] ?? now()->endOfMonth()->toDateString();

        if (Carbon::parse($start)->greaterThan(Carbon::parse($end))) {
            [$start, $end] = [$end, $start];
        }

        $this->startDate = $start;
        $this->endDate = $end;
    }

    protected function getDateRange(): array
    {
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    public function getInvoicesProperty()
    {
        [$start, $end] = $this->getDateRange();

        return CompanyInvoice::query()
            ->where('status', '!=', 'Draft')
            ->whereBetween('period_start', [$start, $end])
            ->orderByDesc('period_start')
            ->get();
    }

    public function getPayrollsProperty()
    {
        [$start, $end] = $this->getDateRange();

        return Payroll::query()
            ->with('user')
            ->where('status', PayrollStatus::Paid)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getRevenueTotal(): float
    {
        [$start, $end] = $this->getDateRange();

        return (float) CompanyInvoice::query()
            ->where('status', '!=', 'Draft')
            ->whereBetween('period_start', [$start, $end])
            ->sum('total_amount');
    }

    public function getPayrollTotal(): float
    {
        [$start, $end] = $this->getDateRange();

        return (float) Payroll::query()
            ->where('status', PayrollStatus::Paid)
            ->whereBetween('created_at', [$start, $end])
            ->sum('net_pay');
    }

    public function getNetProfit(): float
    {
        return $this->getRevenueTotal() - $this->getPayrollTotal();
    }

    public function getMarginPercent(): float
    {
        $revenue = $this->getRevenueTotal();
        $profit = $this->getNetProfit();

        return $revenue > 0.0 ? round(($profit / $revenue) * 100, 2) : 0.0;
    }

    public function getChartDataset(): array
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
        $revenue = [];
        $payroll = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $revenue[] = (float) ($invoiceMap[$key] ?? 0);
            $payroll[] = (float) ($payrollMap[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'payroll' => $payroll,
        ];
    }
}
