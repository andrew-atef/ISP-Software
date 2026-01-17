<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'wire3_email',
        'job_title',
        'current_lat',
        'current_lng',
        'last_seen_at',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Enums\UserRole::class,
            'last_seen_at' => 'datetime',
            'current_lat' => 'float',
            'current_lng' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_tech_id');
    }

    public function inventoryWallet(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'inventory_wallets')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function inventoryWallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryWallet::class);
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanInstallments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(LoanInstallment::class, Loan::class);
    }

    public function payrolls(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function truckStock(int $itemId): int
    {
        return $this->inventoryWallet()->where('inventory_item_id', $itemId)->value('quantity') ?? 0;
    }

    /**
     * Get current week earnings (pending payment).
     *
     * Returns sum of tech_price for Approved tasks completed this week
     * that haven't been assigned to a payroll record yet.
     */
    public function getCurrentWeekEarningsAttribute(): float
    {
        $start = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::SUNDAY);
        return (float) $this->tasks()
            ->where('status', \App\Enums\TaskStatus::Approved)
            ->where('completion_date', '>=', $start)
            ->whereNull('payroll_id') // Exclude tasks already assigned to payroll
            ->sum('tech_price');
    }
}
