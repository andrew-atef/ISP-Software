<?php

namespace App\Models;

use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'parent_task_id',
        'original_tech_id',
        'assigned_tech_id',
        'task_type',
        'status',
        'financial_status',
        'company_price',
        'tech_price',
        'scheduled_date',
        'time_slot_start',
        'time_slot_end',
        'saf_link',
        'description',
        'import_batch_id',
        'completion_date',
        'is_offline_sync',
        'company_invoice_id',
        'payroll_id',
    ];

    protected $casts = [
        'task_type' => TaskType::class,
        'status' => TaskStatus::class,
        'financial_status' => TaskFinancialStatus::class,
        'company_price' => 'decimal:2',
        'tech_price' => 'decimal:2',
        'scheduled_date' => 'date',
        'completion_date' => 'datetime',
        'is_offline_sync' => 'boolean',
    ];

    // Relations

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subTasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function originalTech()
    {
        return $this->belongsTo(OriginalTech::class, 'original_tech_id');
    }

    public function assignedTech()
    {
        return $this->belongsTo(User::class, 'assigned_tech_id');
    }

    public function detail()
    {
        return $this->hasOne(TaskDetail::class);
    }

    public function media()
    {
        return $this->hasMany(TaskMedia::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // Scopes

    public function scopeBillable(Builder $query): Builder
    {
        return $query->where('financial_status', TaskFinancialStatus::Billable);
    }

    // Methods

    /**
     * Returns the tech payment for this task.
     * 
     * This method strictly returns the stored tech_price snapshot from the DB.
     * It does NOT calculate anything - it preserves historical financial accuracy.
     *
     * @return float The tech price, or 0.00 if null.
     */
    public function calculateTechPay(): float
    {
        return $this->tech_price ?? 0.00;
    }
    public function companyInvoice()
    {
        return $this->belongsTo(CompanyInvoice::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
