<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Started = 'started';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case ReturnedForFix = 'returned_for_fix';
    case Approved = 'approved';
}
