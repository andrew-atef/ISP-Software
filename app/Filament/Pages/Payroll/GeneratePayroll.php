<?php

namespace App\Filament\Pages\Payroll;

use App\Enums\PayrollStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Payroll;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GeneratePayroll extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string | \UnitEnum | null $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.payroll.generate-payroll';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'year' => now()->year,
            'week_number' => now()->subWeek()->isoWeek,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('technician_id')
                ->label('Technician')
                ->helperText('Leave empty to process ALL technicians.')
                ->options(
                    User::where('role', 'Tech')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable()
                ->nullable()
                ->columnSpan(1),

            Forms\Components\TextInput::make('year')
                ->label('Year')
                ->numeric()
                ->required()
                ->default(fn() => now()->year)
                ->minValue(2020)
                ->maxValue(2099)
                ->columnSpan(1),

            Forms\Components\TextInput::make('week_number')
                ->label('Week Number')
                ->numeric()
                ->required()
                ->default(fn() => now()->subWeek()->isoWeek)
                ->minValue(1)
                ->maxValue(53)
                ->columnSpan(1),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormColumns(): int
    {
        return 3;
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        try {
            // Validate inputs
            if (empty($data['year']) || empty($data['week_number'])) {
                Notification::make()
                    ->title('Validation Error')
                    ->body('Year and Week Number are required.')
                    ->danger()
                    ->send();
                return;
            }

            // Get the week date range
            [$weekStart, $weekEnd] = Payroll::getWeekDateRange((int) $data['year'], (int) $data['week_number']);

            // Fetch technicians to process
            $techniciansQuery = User::where('role', 'Tech');
            if (!empty($data['technician_id'])) {
                $techniciansQuery->where('id', $data['technician_id']);
            }
            $technicians = $techniciansQuery->get();

            if ($technicians->isEmpty()) {
                Notification::make()
                    ->title('No Technicians Found')
                    ->body('No technicians match the filter criteria.')
                    ->warning()
                    ->send();
                return;
            }

            $payrollsGenerated = 0;

            // Process each technician
            foreach ($technicians as $technician) {
                // Fetch payable tasks (not yet paid)
                // BUSINESS RULE: Only Approved tasks (passed QC) are payable
                $tasks = Task::query()
                    ->where('assigned_tech_id', $technician->id)
                    ->where('status', TaskStatus::Approved)
                    ->where('tech_price', '>', 0)
                    ->whereNull('payroll_id')
                    ->whereBetween('completion_date', [$weekStart, $weekEnd])
                    ->get();

                // Fetch loan installments due in this period
                $loanInstallments = $technician
                    ->loanInstallments()
                    ->where('due_date', '<=', $weekEnd->toDateString())
                    ->where('is_paid', false)
                    ->whereNull('payroll_id')
                    ->get();

                // Skip if no tasks or installments
                if ($tasks->isEmpty() && $loanInstallments->isEmpty()) {
                    continue;
                }

                // Create the Payroll record
                $payroll = Payroll::create([
                    'user_id' => $technician->id,
                    'year' => $data['year'],
                    'week_number' => $data['week_number'],
                    'status' => PayrollStatus::Draft,
                    'gross_amount' => 0,
                    'bonus_amount' => 0,
                    'deductions_amount' => 0,
                    'net_pay' => 0,
                ]);

                // Link tasks to payroll
                if ($tasks->isNotEmpty()) {
                    Task::whereIn('id', $tasks->pluck('id'))->update(['payroll_id' => $payroll->id]);
                }

                // Link loan installments to payroll
                if ($loanInstallments->isNotEmpty()) {
                    $loanInstallments->each(function ($installment) use ($payroll) {
                        $installment->update(['payroll_id' => $payroll->id]);
                    });
                }

                // Recalculate payroll totals
                $payroll->recalculate();

                $payrollsGenerated++;
            }

            // Provide feedback
            Notification::make()
                ->title('Success')
                ->body("Generated {$payrollsGenerated} payroll(s) for Week {$data['week_number']}, {$data['year']}.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error generating payrolls: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationLabel(): string
    {
        return 'Generate Payroll';
    }
}
