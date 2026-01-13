<?php

namespace App\Enums;

enum InventoryItemType: string
{
    case Indoor = 'indoor';
    case Outdoor = 'outdoor';
    case Tool = 'tool';
}
