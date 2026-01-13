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
    ];

    protected $casts = [
        'type' => InventoryItemType::class,
    ];

    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryWallet::class);
    }
}
