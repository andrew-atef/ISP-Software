<?php

namespace App\Models;

use Carbon\Carbon;
use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model implements Eventable
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

    public function toCalendarEvent(): CalendarEvent
    {
        $start = $this->buildScheduledDateTime($this->time_slot_start);
        $end = $this->buildScheduledDateTime($this->time_slot_end);

        $isAllDay = $this->time_slot_start === null;

        if ($isAllDay) {
            $start ??= Carbon::make($this->scheduled_date)?->startOfDay();
            $end ??= $start?->copy()?->addDay(); // Full-day block
        } else {
            if ($start && ! $end) {
                $end = (clone $start)->addHour();
            }

            if (! $start && $end) {
                $start = Carbon::make($this->scheduled_date)?->startOfDay();
            }

            if ($start && $end && $end->lessThanOrEqualTo($start)) {
                $end = (clone $start)->addHour();
            }
        }

        $start ??= Carbon::now()->startOfDay();
        $end ??= (clone $start)->addHour();

        $event = CalendarEvent::make($this)
            ->title($this->getCalendarTitle())
            ->start($start)
            ->end($end)
            ->backgroundColor($this->getStatusColor())
            ->action('view');

        if ($isAllDay) {
            $event->allDay();
        }

        return $event;
    }

    protected function getCalendarTitle(): string
    {
        // $customerName = $this->customer?->name ?? 'Customer';
        $customerName ='';
        $taskLabel = $this->task_type?->getLabel() ?? ucfirst(str_replace('_', ' ', (string) $this->task_type));

        return "{$customerName} - {$taskLabel}";
    }

    protected function getStatusColor(): string
    {
        return match ($this->status) {
            TaskStatus::Approved => '#22c55e', // Green
            TaskStatus::Pending => '#fb923c', // Orange
            TaskStatus::Assigned => '#3b82f6', // Blue
            TaskStatus::Started => '#0ea5e9', // Sky
            TaskStatus::Paused => '#a855f7', // Purple
            TaskStatus::Completed => '#10b981', // Emerald
            TaskStatus::ReturnedForFix => '#f59e0b', // Amber
            TaskStatus::Cancelled => '#ef4444', // Red
            default => '#6b7280', // Gray fallback
        };
    }

    protected function buildScheduledDateTime(?string $time): ?Carbon
    {
        if (! $this->scheduled_date) {
            return null;
        }

        $dateString = Carbon::make($this->scheduled_date)?->toDateString();

        if (! $dateString) {
            return null;
        }

        if ($time === null) {
            return Carbon::make($dateString)?->startOfDay();
        }

        $combined = Carbon::make($dateString . ' ' . $time);

        return $combined?->copy();
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
