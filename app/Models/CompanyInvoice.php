<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'period_start',
        'period_end',
        'week_number',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public static function booted()
    {
        static::creating(function ($invoice) {
            if (! $invoice->invoice_number) {
                // FIX: Use period_start (invoice period), not now() (current timestamp)
                // This ensures invoice numbers are based on the period being invoiced,
                // not when the invoice is created (important for backdated invoices)
                $periodDate = Carbon::parse($invoice->period_start);

                $year = $periodDate->format('Y');
                $month = $periodDate->format('m');
                $week = str_pad($periodDate->weekOfYear, 2, '0', STR_PAD_LEFT);
                $base = "INV-{$year}{$month}{$week}";

                $number = $base;
                $suffix = 1;

                while (static::where('invoice_number', $number)->exists()) {
                    $number = "{$base}-" . str_pad($suffix, 2, '0', STR_PAD_LEFT);
                    $suffix++;
                }

                $invoice->invoice_number = $number;
            }
        });
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
