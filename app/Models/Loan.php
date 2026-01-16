<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'amount_total',
        'installments_count',
        'installment_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'amount_total' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'status' => LoanStatus::class,
    ];

    protected static function booted(): void
    {
        static::created(function (Loan $loan) {
            $loan->createInstallments();
        });
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function installments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    /**
     * Create LoanInstallment records for this loan.
     *
     * FIX: Single Source of Truth for installment generation.
     * Handles the "Penny Problem":
     * - Calculates base installment amount (rounded down to 2 decimals)
     * - Adds remainder to the LAST installment
     * - Ensures Sum(installments) == Total Loan (financial accuracy)
     *
     * Schedule: Weekly (since Payroll is weekly)
     */
    public function createInstallments(): void
    {
        $totalAmount = (float) $this->amount_total;
        $installmentsCount = (int) $this->installments_count;

        if ($installmentsCount <= 0) {
            return;
        }

        // Wrap in transaction for atomicity - all or nothing
        DB::transaction(function () use ($totalAmount, $installmentsCount) {
            // Calculate base installment amount (floor to 2 decimals to avoid over-deduction)
            $baseInstallmentAmount = floor(($totalAmount / $installmentsCount) * 100) / 100;

            // Calculate remainder (The "Penny Problem")
            // This is the amount that would be lost due to rounding
            $remainder = round($totalAmount - ($baseInstallmentAmount * $installmentsCount), 2);

            // Starting date for first installment
            $startDate = $this->start_date ? \Carbon\Carbon::parse($this->start_date) : now();

            // Create installments
            for ($i = 0; $i < $installmentsCount; $i++) {
                $isLastInstallment = ($i === $installmentsCount - 1);

                // Add remainder to last installment to ensure sum matches total
                $installmentAmount = $isLastInstallment
                    ? $baseInstallmentAmount + $remainder
                    : $baseInstallmentAmount;

                // Calculate due date (Weekly schedule)
                // First installment is on start_date, subsequent are +1 week each
                $dueDate = $startDate->copy()->addWeeks($i);

                $this->installments()->create([
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate->toDateString(),
                    'is_paid' => false,
                    'payroll_id' => null,
                ]);
            }
        });
    }
}
