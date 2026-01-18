<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JobPrice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'task_type',
        'company_price',
        'tech_price',
    ];

    protected $casts = [
        'task_type' => \App\Enums\TaskType::class,
        'company_price' => 'decimal:2',
        'tech_price' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('job_price')
            ->logOnly([
                'task_type',
                'company_price',
                'tech_price',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "JobPrice {$eventName}");
    }
}
