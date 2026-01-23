<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_request_id',
        'inventory_item_id',
        'item_name',
        'quantity_requested',
    ];

    public function request()
    {
        return $this->belongsTo(InventoryRequest::class, 'inventory_request_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
