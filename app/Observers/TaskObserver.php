<?php

namespace App\Observers;

use App\Enums\InstallationType;
use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\JobPrice;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\InventoryTransaction;
use App\Models\InventoryWallet;
use Illuminate\Support\Facades\DB;

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
     * Triggers two automated actions:
     * 1. DropBury sub-task generation when NewInstall completed without bury
     * 2. Inventory consumption deduction when task is Completed/Approved
     */
    public function updated(Task $task): void
    {
        $this->handleDropBurySubTaskGeneration($task);
        $this->handleInventoryConsumption($task);
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

        // If install type is Aerial or MDU, no DropBury child should be created
        $installType = $task->installation_type ?? $detail->installation_type ?? null;
        if (in_array($installType, [InstallationType::Aerial, InstallationType::MDU], true)) {
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
        // SUB-TASK GENERATION (AFTER COMMIT)
        // =====================

        DB::afterCommit(function () use ($task) {
            $freshTask = Task::with('detail')->find($task->id);

            if (!$freshTask || !$freshTask->detail) {
                return;
            }

            $freshDetail = $freshTask->detail;

            // Re-check install type rules after reload
            $installType = $freshTask->installation_type ?? $freshDetail->installation_type ?? null;
            if (in_array($installType, [InstallationType::Aerial, InstallationType::MDU], true)) {
                return;
            }

            // Re-check drop bury status after reload
            if ($freshDetail->drop_bury_status === true) {
                return;
            }

            // Prevent duplicates
            $exists = Task::where('parent_task_id', $freshTask->id)
                ->where('task_type', TaskType::DropBury)
                ->exists();

            if ($exists) {
                return;
            }

            // Pricing
            $dropBuryPrice = JobPrice::where('task_type', TaskType::DropBury)->first();
            $techPrice = (float) ($dropBuryPrice?->tech_price ?? 0.00);

            // Create child task
            $dropBuryTask = Task::create([
                'parent_task_id' => $freshTask->id,
                'customer_id' => $freshTask->customer_id,
                'assigned_tech_id' => null,
                'original_tech_id' => $freshTask->original_tech_id,
                'task_type' => TaskType::DropBury,
                'status' => TaskStatus::Pending,
                'financial_status' => TaskFinancialStatus::NotBillable,
                'company_price' => 0.00,
                'tech_price' => $techPrice,
                'scheduled_date' => $freshTask->scheduled_date,
                'time_slot_start' => null,
                'time_slot_end' => null,
                'description' => "Auto-generated DropBury sub-task for Task #{$freshTask->id}",
                'saf_link' => $freshTask->saf_link,
                'import_batch_id' => $freshTask->import_batch_id,
            ]);

            // Copy tech notes from parent task detail if present
            if ($freshDetail->tech_notes) {
                TaskDetail::updateOrCreate(
                    ['task_id' => $dropBuryTask->id],
                    ['tech_notes' => $freshDetail->tech_notes]
                );
            }
        });
    }

    /**
     * Handle inventory consumption deduction.
     *
     * When a Task is marked as Completed or Approved:
     * - Query the task's inventory_consumptions (tracked items only)
     * - Deduct quantities from the assigned technician's wallet
     * - Log transactions for audit trail
     *
     * Business Rule:
     * - Only deduct once: Check if already processed via a flag or by checking
     *   existing transactions for this task
     */
    protected function handleInventoryConsumption(Task $task): void
    {
        // =====================
        // TRIGGER CONDITIONS
        // =====================

        // 1. Only run if status changed to Completed or Approved
        if (!$task->wasChanged('status')) {
            return;
        }

        if (!in_array($task->status, [TaskStatus::Completed, TaskStatus::Approved])) {
            return;
        }

        // 2. Task must have an assigned technician
        if (!$task->assigned_tech_id) {
            return;
        }

        // 3. Skip if task has no inventory consumptions recorded
        $consumptions = $task->inventoryConsumptions;
        if ($consumptions->isEmpty()) {
            return;
        }

        // =====================
        // IDEMPOTENCY CHECK
        // =====================

        // Check if inventory was already deducted for this task
        $alreadyProcessed = InventoryTransaction::where('task_id', $task->id)
            ->where('type', 'consumed')
            ->exists();

        if ($alreadyProcessed) {
            return; // Prevent duplicate deductions
        }

        // =====================
        // INVENTORY DEDUCTION
        // =====================

        foreach ($consumptions as $consumption) {
            $quantity = $consumption->quantity;
            $itemId = $consumption->inventory_item_id;
            $techId = $task->assigned_tech_id;

            // Get or create wallet entry for tech
            $wallet = InventoryWallet::firstOrCreate(
                ['user_id' => $techId, 'inventory_item_id' => $itemId],
                ['quantity' => 0]
            );

            // Deduct from wallet
            $wallet->decrement('quantity', $quantity);

            // Log transaction for audit trail
            InventoryTransaction::create([
                'inventory_item_id' => $itemId,
                'source_user_id' => $techId,
                'target_user_id' => null,
                'task_id' => $task->id,
                'quantity' => $quantity,
                'type' => 'consumed',
                'notes' => "Consumed during {$task->task_type->getLabel()} task (Task #{$task->id})",
            ]);
        }
    }
}
