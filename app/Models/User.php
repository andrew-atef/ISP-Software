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
            'current_lat' => 'decimal:8',
            'current_lng' => 'decimal:8',
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

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function payrolls(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function truckStock(int $itemId): int
    {
        return $this->inventoryWallet()->where('inventory_item_id', $itemId)->value('quantity') ?? 0;
    }

    public function getCurrentWeekEarningsAttribute(): float
    {
        $start = \Carbon\Carbon::now()->startOfWeek();
        return $this->tasks()
            ->where('status', \App\Enums\TaskStatus::Approved)
            ->where('completion_date', '>=', $start)
            ->sum('tech_price');
    }
}
