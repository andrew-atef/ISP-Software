<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class InventoryWallet extends Pivot
{
    // Since it's a pivot, we can extend Pivot.
    // However, if we want to use it as a standalone model queried directly, we can extend Model and specify table.
    // The user asked for "Pivot: User <-> Item".
    // I'll make it a Pivot class but check if I need to use it as a standard model for some queries.
    // Usually extending Pivot is fine if using `->withPivot` on relations.
    // If I use `InventoryWallet::create(...)`, I should set `$table`.

    protected $table = 'inventory_wallets';

    public $incrementing = true; // Use ID if migration has ID, usually pivots don't but the prompt just listed columns. I'll check migration plan.
    // "inventory_wallets: user_id, inventory_item_id, quantity"
    // I will assume it might not have an 'id' primary key unless I add it.
    // Best practice for pure pivot is no ID, but if we want to track it easily, ID is good.
    // For now, I'll treat it as a Pivot for the User relationship.

    protected $fillable = [
        'user_id',
        'inventory_item_id',
        'quantity',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
