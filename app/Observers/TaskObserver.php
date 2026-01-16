<?php

namespace App\Observers;

use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\JobPrice;
use App\Models\Task;

class TaskObserver
{
    /**
     * Handle the Task "saving" event.
     *
     * This observer implements a "Safety Net" logic:
     * - ONLY fills prices if they are null AND not dirty (not manually set)
     * - Respects manual input from Form/User
     * - Sources default values from JobPrice model
     */
    public function saving(Task $task): void
    {
        // Early return: If both prices are dirty (manually set), respect user input
        if ($task->isDirty('company_price') && $task->isDirty('tech_price')) {
            return;
        }

        // Early return: If task_type is not set, we cannot determine pricing
        if (!$task->task_type) {
            return;
        }

        // Fetch pricing from JobPrice (Single Source of Truth)
        $jobPrice = JobPrice::where('task_type', $task->task_type)->first();

        // Only fill company_price if it's null AND not manually set
        if ($task->company_price === null && !$task->isDirty('company_price')) {
            $task->company_price = (float) ($jobPrice?->company_price ?? 0.00);
        }

        // Only fill tech_price if it's null AND not manually set
        if ($task->tech_price === null && !$task->isDirty('tech_price')) {
            $task->tech_price = (float) ($jobPrice?->tech_price ?? 0.00);
        }
    }

    /**
     * Handle the Task "updated" event.
     *
     * Triggers automated sub-task generation for "Split Scenarios":
     * When a NewInstall task is marked Completed but drop_bury is NOT done,
     * automatically create a DropBury sub-task.
     */
    public function updated(Task $task): void
    {
        $this->handleDropBurySubTaskGeneration($task);
    }

    /**
     * Handle automated DropBury sub-task generation.
     *
     * Business Rules ("The Constitution"):
     * - Trigger: status changed to Completed AND task_type is NewInstall
     * - Check: If drop_bury_status is true, do nothing (job fully complete)
     * - Action: If drop_bury_status is false, create DropBury sub-task
     * - Idempotency: Prevent duplicate sub-tasks
     */
    protected function handleDropBurySubTaskGeneration(Task $task): void
    {
        // =====================
        // TRIGGER CONDITIONS
        // =====================

        // 1. Only run if status was just changed to Completed
        if (!$task->wasChanged('status')) {
            return;
        }

        if ($task->status !== TaskStatus::Completed) {
            return;
        }

        // 2. Only run for NewInstall tasks
        if ($task->task_type !== TaskType::NewInstall) {
            return;
        }

        // 3. Only run for parent tasks (not sub-tasks themselves)
        if ($task->parent_task_id !== null) {
            return;
        }

        // =====================
        // DATA VERIFICATION
        // =====================

        // Retrieve the associated TaskDetail
        // RACE CONDITION FIX: Fresh load in case detail was created in same transaction
        $detail = $task->detail;

        if (!$detail) {
            // Attempt fresh load from database
            $task->load('detail');
            $detail = $task->detail;
        }

        // If no detail exists, we can't determine bury status - skip
        // IMPORTANT: For offline sync (is_offline_sync=true), ensure TaskDetail
        // is saved BEFORE Task status is updated to Completed to avoid this scenario
        if (!$detail) {
            return;
        }

        // If drop_bury_status is TRUE (Bury done): Job fully complete, do nothing
        if ($detail->drop_bury_status === true) {
            return;
        }

        // =====================
        // IDEMPOTENCY CHECK (Safety Net)
        // =====================

        // Check if a DropBury sub-task already exists for this parent
        $existingSubTask = Task::where('parent_task_id', $task->id)
            ->where('task_type', TaskType::DropBury)
            ->exists();

        if ($existingSubTask) {
            return; // Prevent duplicate sub-tasks
        }

        // =====================
        // SUB-TASK GENERATION
        // =====================

        // Fetch the standard DropBury rate from JobPrice
        $dropBuryPrice = JobPrice::where('task_type', TaskType::DropBury)->first();
        $techPrice = (float) ($dropBuryPrice?->tech_price ?? 0.00);

        // Create the DropBury sub-task (The Child)
        Task::create([
            // Relationships
            'parent_task_id' => $task->id,
            'customer_id' => $task->customer_id,

            // Assignment: null for Dispatcher to re-assign
            'assigned_tech_id' => null,
            'original_tech_id' => $task->original_tech_id,

            // Task Type & Status
            'task_type' => TaskType::DropBury,
            'status' => TaskStatus::Pending,

            // Financials (CRITICAL)
            // Parent task bills the $350, this child is cost-only
            'financial_status' => TaskFinancialStatus::NotBillable,
            'company_price' => 0.00,
            'tech_price' => $techPrice,

            // Scheduling: inherit parent's scheduled date, no specific time
            'scheduled_date' => $task->scheduled_date,
            'time_slot_start' => null,
            'time_slot_end' => null,

            // Metadata
            'description' => "Auto-generated DropBury sub-task for Task #{$task->id}",
            'saf_link' => $task->saf_link,
            'import_batch_id' => $task->import_batch_id,
        ]);
    }
}
