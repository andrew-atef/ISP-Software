<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case Restock = 'restock';
    case Transfer = 'transfer';
    case Consumed = 'consumed';
    case Return = 'return';
}
