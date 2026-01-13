<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount_total',
        'installments_count',
        'installment_amount',
        'status',
        'notes',
    ];

    protected $casts = [
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

    public function createInstallments(): void
    {
        // Automatically generate rows in loan_installments table
        // e.g., $100 loan paid over 4 weeks = $25/week deduction.
        // Assuming weekly installments starting next week or immediate? Prompt didn't specify start date logic.
        // I'll assume starting 1 week from creation for simplicity.

        $startDate = now();

        for ($i = 1; $i <= $this->installments_count; $i++) {
            $this->installments()->create([
                'amount' => $this->installment_amount,
                'due_date' => $startDate->copy()->addWeeks($i),
                'is_paid' => false,
            ]);
        }
    }
}
