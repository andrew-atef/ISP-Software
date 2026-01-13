<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskDetail extends Model
{
    use HasFactory;

    protected $touches = ['task'];

    protected $fillable = [
        'task_id',
        'ont_serial',
        'eero_serial_1',
        'eero_serial_2',
        'eero_serial_3',
        'drop_bury_status',
        'sidewalk_bore_status',
        'start_time_actual',
        'end_time_actual',
        'tech_notes',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
    ];

    protected $casts = [
        'drop_bury_status' => 'boolean',
        'sidewalk_bore_status' => 'boolean',
        'start_time_actual' => 'datetime',
        'end_time_actual' => 'datetime',
        'start_lat' => 'decimal:8',
        'start_lng' => 'decimal:8',
        'end_lat' => 'decimal:8',
        'end_lng' => 'decimal:8',
    ];

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
