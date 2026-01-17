<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskInventoryConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'inventory_item_id',
        'quantity',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
