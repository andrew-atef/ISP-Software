<?php

namespace App\Models;

use App\Enums\InventoryItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'type',
        'description',
        'is_tracked',
    ];

    protected $casts = [
        'type' => InventoryItemType::class,
        'is_tracked' => 'boolean',
    ];

    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryWallet::class);
    }
}
