<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Enums\TaskStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payroll extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'week_number',
        'year',
        'gross_amount',
        'bonus_amount',
        'deductions_amount',
        'deduction_override',
        'net_pay',
        'status',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'deductions_amount' => 'decimal:2',
        'deduction_override' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'status' => PayrollStatus::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logOnly([
                'status',
                'gross_amount',
                'bonus_amount',
                'deductions_amount',
                'deduction_override',
                'net_pay',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Payroll {$eventName}");
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanInstallments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get precise week date range.
     *
     * FIX #2: Centralized date precision logic.
     * Uses startOfDay/endOfDay for exact boundaries.
     */
    public static function getWeekDateRange(int $year, int $week): array
    {
        $start = Carbon::now()
            ->setISODate($year, $week)
            ->startOfWeek(Carbon::SUNDAY)
            ->startOfDay();

        $end = $start->copy()
            ->endOfWeek(Carbon::SATURDAY)
            ->endOfDay();

        return [$start, $end];
    }

    /**
     * Recalculate payroll financials from linked tasks and loan installments.
     *
     * This method re-sums all linked tasks (tech_price) and loan installments (amount)
     * to compute Gross, Deductions, and Net Pay amounts.
     *
     * Mathematical breakdown:
     * - gross_amount = sum of all linked tasks' tech_price (ONLY Approved status)
     * - deductions_amount = sum of all linked loan installments' amount (or deduction_override if set)
     * - net_pay = gross_amount + bonus_amount - deductions_amount
     *
     * BUSINESS RULE: Only Approved tasks (passed QC) are payable.
     * This is a WRITE operation that updates this Payroll record's financial totals.
     */
    public function recalculate(): void
    {
        // Sum tech_price from all linked tasks that are Approved (passed QC)
        $grossAmount = $this->tasks()
            ->where('status', TaskStatus::Approved)
            ->sum('tech_price');

        // Sum amount from all linked loan installments
        $deductionsAmount = $this->loanInstallments()
            ->sum('amount');

        // Use deduction_override if explicitly set, otherwise use calculated deductions
        $finalDeductions = $this->deduction_override ?? (float) $deductionsAmount;

        // Calculate net pay: gross + bonus - deductions
        $netPay = $grossAmount + ($this->bonus_amount ?? 0) - $finalDeductions;

        // Update this record with calculated totals
        $this->update([
            'gross_amount' => $grossAmount,
            'deductions_amount' => $deductionsAmount,
            'net_pay' => $netPay,
        ]);
    }
}
