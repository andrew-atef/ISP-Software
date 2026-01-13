<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payroll extends Model
{
    use HasFactory;

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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanInstallments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function recalculate(): void
    {
        // 1. Determine Week Range based on stored week_number/year
        // ISO-8601 weeks start on Monday. Adjust if business needs Sunday.
        // Assuming Carbon's default startOfWeek() (Monday) for now.
        $start = Carbon::now()->setISODate($this->year, $this->week_number)->startOfWeek(Carbon::SUNDAY);
        $end = $start->copy()->endOfWeek(Carbon::SATURDAY);

        // 2. Gross Earnings from Tasks
        // Sum tech_price of tasks completed in this window
        $tasksTotal = Task::where('assigned_tech_id', $this->user_id)
            ->where('status', \App\Enums\TaskStatus::Approved)
            ->whereBetween('completion_date', [$start, $end])
            ->sum('tech_price');

        // Apply Bonus
        $gross = $tasksTotal + ($this->bonus_amount ?? 0);

        // 3. Deductions from Loans
        // Find installments due this week, not yet assigned to ANOTHER payroll (or assigned to this one)
        $installments = LoanInstallment::whereHas('loan', function ($q) {
            $q->where('user_id', $this->user_id);
        })
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->whereNull('payroll_id')
                    ->orWhere('payroll_id', $this->id);
            })
            ->get();

        $systemDeductions = $installments->sum('amount');

        // Apply Override if set
        $finalDeductions = $this->deduction_override ?? $systemDeductions;

        // Link these installments to this payroll record
        foreach ($installments as $installment) {
            $installment->update(['payroll_id' => $this->id]);
        }

        // 4. Update Totals
        $this->updateQuietly([
            'gross_amount' => $gross,
            'deductions_amount' => $finalDeductions,
            'net_pay' => $gross - $finalDeductions,
        ]);
    }
}
