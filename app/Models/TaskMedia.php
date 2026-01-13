<?php

namespace App\Models;

use App\Enums\TaskMediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'file_path',
        'type',
        'watermark_data',
        'taken_at',
    ];

    protected $casts = [
        'type' => TaskMediaType::class,
        'watermark_data' => 'array',
        'taken_at' => 'datetime',
    ];

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
