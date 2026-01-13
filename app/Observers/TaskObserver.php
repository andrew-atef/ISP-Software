<?php

namespace App\Observers;

use App\Enums\TaskType;
use App\Models\Task;

class TaskObserver
{
    public function saving(Task $task): void
    {
        $basePrice = 0.0;

        // Base price calculation
        switch ($task->task_type) {
            case TaskType::NewInstall:
                $basePrice = 50.00;
                break;
            case TaskType::ServiceCall:
                $basePrice = 30.00;
                break;
            case TaskType::ServiceChange:
                $basePrice = 20.00;
                break;
            case TaskType::DropBury:
                $basePrice = 40.00;
                break;
            default:
                $basePrice = 0.0;
        }

        // Additions from TaskDetail
        $additions = 0.0;
        // Accessing relationship might require loading, but usually 'detail' is available if loaded.
        // If logic runs on Task save, detail might be separate query.
        // We use fresh instance or relation to check.
        // Note: relation access via $task->detail returns model or null.

        $detail = $task->detail;

        if ($detail) {
            if ($detail->drop_bury_status) {
                $additions += 20.00;
            }
            if ($detail->sidewalk_bore_status) {
                $additions += 40.00;
            }
        }

        $task->tech_price = $basePrice + $additions;
    }
}
