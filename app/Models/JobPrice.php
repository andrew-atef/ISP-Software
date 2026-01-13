<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPrice extends Model
{
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
}
