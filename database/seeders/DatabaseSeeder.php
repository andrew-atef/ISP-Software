<?php

namespace Database\Seeders;

use App\Enums\TaskFinancialStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Enums\UserRole;
use App\Enums\InventoryItemType;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryWallet;
use App\Models\JobPrice;
use App\Models\Loan;
use App\Models\OriginalTech;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(JobPriceSeeder::class);

        // =====================
        // USERS
        // =====================
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'phone' => '+1234567890',
            'job_title' => 'System Administrator',
        ]);

        $islamYoussef = User::create([
            'name' => 'Islam Youssef',
            'email' => 'islam@xconnect.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Tech,
            'phone' => '+1987654321',
            'job_title' => 'Fiber Technician',
            'wire3_email' => 'islam.youssef@wire3.com',
            'current_lat' => 28.538336,
            'current_lng' => -81.379234,
        ]);

        $mouradShokralla = User::create([
            'name' => 'Mourad Shokralla',
            'email' => 'mourad@xconnect.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Tech,
            'phone' => '+1555123456',
            'job_title' => 'Senior Fiber Technician',
            'wire3_email' => 'mourad.shokralla@wire3.com',
            'current_lat' => 28.419411,
            'current_lng' => -81.581207,
        ]);

        // =====================
        // ORIGINAL TECHS
        // =====================
        $techXC5 = OriginalTech::create(['name' => 'Islam Youssef (XC5)', 'code' => 'XC5']);
        $techXC1 = OriginalTech::create(['name' => 'Mourad Shokralla (XC1)', 'code' => 'XC1']);

        // =====================
        // INVENTORY ITEMS
        // =====================
        $eeroPro7 = InventoryItem::create([
            'name' => 'EERO Pro 7', 'sku' => 'EERO-PRO-7', 'type' => InventoryItemType::Indoor, 'description' => 'High-performance mesh WiFi router.',
        ]);
        $nokiaOnt = InventoryItem::create([
            'name' => 'Nokia ONT', 'sku' => 'NOKIA-ONT-G1', 'type' => InventoryItemType::Indoor, 'description' => 'Optical Network Terminal.',
        ]);
        $fiberCable = InventoryItem::create([
            'name' => 'Fiber Cable (100m)', 'sku' => 'FIBER-CBL-100M', 'type' => InventoryItemType::Outdoor, 'description' => '100 meter fiber optic cable roll.',
        ]);

        // =====================
        // INVENTORY WALLET
        // =====================
        InventoryWallet::create(['user_id' => $islamYoussef->id, 'inventory_item_id' => $eeroPro7->id, 'quantity' => 50]);
        InventoryWallet::create(['user_id' => $islamYoussef->id, 'inventory_item_id' => $nokiaOnt->id, 'quantity' => 30]);
        InventoryWallet::create(['user_id' => $islamYoussef->id, 'inventory_item_id' => $fiberCable->id, 'quantity' => 15]);
        InventoryWallet::create(['user_id' => $mouradShokralla->id, 'inventory_item_id' => $eeroPro7->id, 'quantity' => 40]);
        InventoryWallet::create(['user_id' => $mouradShokralla->id, 'inventory_item_id' => $nokiaOnt->id, 'quantity' => 25]);

        // =====================
        // CUSTOMERS
        // =====================
        $customers = [];
        $customerNames = [
            ['John Smith', '+1407-555-1234', '123 Main Street, Orlando, FL 32801', 28.538336, -81.379234],
            ['Maria Garcia', '+1813-555-5678', '456 Oak Avenue, Tampa, FL 33602', 27.950575, -82.457176],
            // ... (باقي العملاء زي ما هما)
            ['David Chen', '+1727-555-9012', '789 Pine Road, St. Petersburg, FL 33701', 27.773056, -82.638889],
            ['Jennifer Lopez', '+1352-555-3456', '321 Elm Street, Gainesville, FL 32601', 29.641634, -82.324593],
            ['Michael Johnson', '+1561-555-7890', '654 Maple Drive, West Palm Beach, FL 33401', 26.715346, -80.053803],
            ['Sarah Williams', '+1954-555-2345', '987 Cedar Lane, Fort Lauderdale, FL 33301', 26.122387, -80.137693],
            ['Robert Brown', '+1786-555-6789', '159 Birch Court, Miami, FL 33101', 25.761681, -80.191788],
            ['Lisa Anderson', '+1239-555-0123', '753 Spruce Way, Naples, FL 34102', 26.140348, -81.794704],
            ['James Taylor', '+1772-555-4567', '357 Ash Boulevard, Port St. Lucie, FL 34952', 27.283806, -80.357784],
            ['Amanda Martinez', '+1904-555-8901', '159 Walnut Street, Jacksonville, FL 32202', 30.335481, -81.659358],
            ['Christopher Davis', '+1321-555-2345', '654 Hickory Road, Melbourne, FL 32901', 28.083236, -80.608139],
            ['Elizabeth Rodriguez', '+1863-555-6789', '321 Dogwood Lane, Lakeland, FL 33801', 28.039465, -81.954453],
            ['Daniel Wilson', '+1850-555-0123', '753 Juniper Street, Tallahassee, FL 32301', 30.438256, -84.280733],
            ['Jessica Lee', '+1904-555-4567', '147 Magnolia Avenue, Jacksonville Beach, FL 32250', 30.293516, -81.389502],
            ['Matthew Garcia', '+1386-555-8901', '852 Palmetto Drive, Daytona Beach, FL 32114', 29.213080, -81.023452],
            ['Ashley Martinez', '+1239-555-2345', '369 Cypress Road, Bonita Springs, FL 34134', 26.356806, -81.791130],
            ['Joshua Anderson', '+1941-555-6789', '258 Laurel Court, Sarasota, FL 34236', 27.330928, -82.533516],
            ['Emily Thompson', '+1561-555-0123', '741 Sycamore Lane, Boca Raton, FL 33432', 26.368917, -80.108014],
            ['Brandon Jackson', '+1352-555-4567', '963 Cottonwood Street, Ocala, FL 34470', 29.187919, -82.139143],
            ['Megan White', '+1407-555-8901', '456 Hackberry Drive, Kissimmee, FL 34741', 28.291564, -81.308994],
        ];

        foreach ($customerNames as $index => $data) {
            $customers[$index] = Customer::create([
                'wire3_cid' => (1000000000 + $index),
                'name' => $data[0], 'phone' => $data[1], 'address' => $data[2], 'lat' => $data[3], 'lng' => $data[4],
            ]);
        }

        // =====================
        // LOANS
        // =====================
        $loan = Loan::create([
            'user_id' => $islamYoussef->id,
            'start_date' => Carbon::create(2026, 1, 1),
            'amount_total' => 1500.00,
            'installments_count' => 3,
            'installment_amount' => 500.00,
            'status' => 'active',
            'notes' => 'Equipment advance loan - 3 weekly installments',
        ]);

        // =====================
        // TASKS
        // =====================
        $taskCounter = 0;
        $tasksCreated = [];
        $getPrice = fn (TaskType $type) => JobPrice::where('task_type', $type)->first();

        // 1. New Install
        for ($i = 0; $i < 30; $i++) {
            $taskCounter++;
            $newInstallPrice = $getPrice(TaskType::NewInstall);

            // Determine status and assign technician accordingly
            if ($i < 15) {
                $status = TaskStatus::Approved;
                $assignedTech = random_int(0, 1) === 0 ? $islamYoussef : $mouradShokralla;
            } elseif ($i < 25) {
                $status = TaskStatus::Completed;
                $assignedTech = random_int(0, 1) === 0 ? $islamYoussef : $mouradShokralla;
            } else {
                // Pending tasks MUST have no assigned technician
                $status = TaskStatus::Pending;
                $assignedTech = null;
            }

            // Generate time slots: start hour (8-15) + duration (1-3 hours)
            $startHour = random_int(8, 15);
            $duration = random_int(1, 3);
            $endHour = $startHour + $duration;
            $timeSlotStart = sprintf('%02d:00', $startHour);
            $timeSlotEnd = sprintf('%02d:00', $endHour);

            // Generate scheduled date and completion date
            $scheduledDay = random_int(1, 20);
            $scheduledDate = Carbon::create(2026, 1, $scheduledDay)->toDateString();

            // For Approved/Completed tasks, set completion_date to scheduled_date + end_time
            if ($status === TaskStatus::Approved || $status === TaskStatus::Completed) {
                $completionDate = Carbon::create(2026, 1, $scheduledDay)->setTime($endHour, 0);
            } else {
                $completionDate = null;
            }

            $customer = $customers[random_int(0, 19)];
            $originalTech = random_int(0, 1) === 0 ? $techXC5 : $techXC1;

            $task = Task::create([
                'customer_id' => $customer->id,
                'parent_task_id' => null,
                'original_tech_id' => $originalTech->id,
                'assigned_tech_id' => $assignedTech?->id,
                'task_type' => TaskType::NewInstall,
                'status' => $status,
                'financial_status' => TaskFinancialStatus::Billable,
                'company_price' => (float) ($newInstallPrice?->company_price ?? 0.00),
                'tech_price' => (float) ($newInstallPrice?->tech_price ?? 0.00),
                'scheduled_date' => $scheduledDate,
                'time_slot_start' => $timeSlotStart,
                'time_slot_end' => $timeSlotEnd,
                'saf_link' => 'https://wire3.com/saf/' . str_pad($taskCounter, 6, '0', STR_PAD_LEFT),
                'description' => "New fiber installation for {$customer->name}. Task #{$taskCounter}.",
                'import_batch_id' => 'BATCH-2026-01-' . str_pad(random_int(1, 16), 2, '0', STR_PAD_LEFT),
                'completion_date' => $completionDate,
                'is_offline_sync' => false,
            ]);
            $tasksCreated[] = $task;

            if ($completionDate) {
                // ... (Create TaskDetail logic remains same)
                TaskDetail::create([
                    'task_id' => $task->id,
                    'ont_serial' => 'NOKIA-ONT-' . strtoupper(bin2hex(random_bytes(4))),
                    'eero_serial_1' => 'EERO-SN-' . str_pad($taskCounter, 5, '0', STR_PAD_LEFT),
                    'eero_serial_2' => 'EERO-SN-' . str_pad($taskCounter + 1, 5, '0', STR_PAD_LEFT),
                    'eero_serial_3' => random_int(0, 1) === 0 ? 'EERO-SN-' . str_pad($taskCounter + 2, 5, '0', STR_PAD_LEFT) : null,
                    'drop_bury_status' => random_int(0, 1) === 0,
                    'sidewalk_bore_status' => random_int(0, 1) === 0,
                    'start_time_actual' => $completionDate->clone()->subHours(random_int(1, 4)),
                    'end_time_actual' => $completionDate,
                    'tech_notes' => "Installation completed successfully. Customer satisfied. Task #{$taskCounter}.",
                    'start_lat' => $customer->lat,
                    'start_lng' => $customer->lng,
                    'end_lat' => $customer->lat,
                    'end_lng' => $customer->lng,
                ]);
            }
        }

        // 2. Drop Bury
        for ($i = 0; $i < 10; $i++) {
            $taskCounter++;
            $dropBuryPrice = $getPrice(TaskType::DropBury);

            // Determine status and assign technician accordingly
            if ($i < 5) {
                $status = TaskStatus::Approved;
                $financialStatus = TaskFinancialStatus::NotBillable;
                $assignedTech = random_int(0, 1) === 0 ? $islamYoussef : $mouradShokralla;
            } else {
                // Pending tasks MUST have no assigned technician
                $status = TaskStatus::Pending;
                $financialStatus = TaskFinancialStatus::NotBillable;
                $assignedTech = null;
            }

            // Generate time slots: start hour (8-15) + duration (1-3 hours)
            $startHour = random_int(8, 15);
            $duration = random_int(1, 3);
            $endHour = $startHour + $duration;
            $timeSlotStart = sprintf('%02d:00', $startHour);
            $timeSlotEnd = sprintf('%02d:00', $endHour);

            // Generate scheduled date and completion date
            $scheduledDay = random_int(1, 20);
            $scheduledDate = Carbon::create(2026, 1, $scheduledDay)->toDateString();

            // For Approved/Completed tasks, set completion_date to scheduled_date + end_time
            if ($status === TaskStatus::Approved || $status === TaskStatus::Completed) {
                $completionDate = Carbon::create(2026, 1, $scheduledDay)->setTime($endHour, 0);
            } else {
                $completionDate = null;
            }

            $customer = $customers[random_int(0, 19)];
            $originalTech = random_int(0, 1) === 0 ? $techXC5 : $techXC1;

            $task = Task::create([
                'customer_id' => $customer->id,
                'parent_task_id' => null,
                'original_tech_id' => $originalTech->id,
                'assigned_tech_id' => $assignedTech?->id,
                'task_type' => TaskType::DropBury,
                'status' => $status,
                'financial_status' => $financialStatus,
                'company_price' => $financialStatus === TaskFinancialStatus::NotBillable ? 0.00 : (float) ($dropBuryPrice?->company_price ?? 0.00),
                'tech_price' => (float) ($dropBuryPrice?->tech_price ?? 0.00),
                'scheduled_date' => $scheduledDate,
                'time_slot_start' => $timeSlotStart,
                'time_slot_end' => $timeSlotEnd,
                'saf_link' => null,
                'description' => "Drop bury work for {$customer->name}.",
                'import_batch_id' => 'BATCH-2026-01-' . str_pad(random_int(1, 16), 2, '0', STR_PAD_LEFT),
                'completion_date' => $completionDate,
                'is_offline_sync' => false,
            ]);
            $tasksCreated[] = $task;

            if ($completionDate) {
                // ... TaskDetail logic
                TaskDetail::create([
                    'task_id' => $task->id,
                    'drop_bury_status' => true,
                    'sidewalk_bore_status' => random_int(0, 1) === 0,
                    'start_time_actual' => $completionDate->clone()->subHours(2),
                    'end_time_actual' => $completionDate,
                    'tech_notes' => "Drop bury completed. Trench depth 2ft as required.",
                    'start_lat' => $customer->lat,
                    'start_lng' => $customer->lng,
                    'end_lat' => $customer->lat,
                    'end_lng' => $customer->lng,
                ]);
            }
        }

        // 3. Service Call
        for ($i = 0; $i < 10; $i++) {
            $taskCounter++;
            $serviceCallPrice = $getPrice(TaskType::ServiceCall);

            // Determine status and assign technician accordingly
            if ($i < 5) {
                $status = TaskStatus::Approved;
                $assignedTech = random_int(0, 1) === 0 ? $islamYoussef : $mouradShokralla;
            } elseif ($i < 8) {
                $status = TaskStatus::Completed;
                $assignedTech = random_int(0, 1) === 0 ? $islamYoussef : $mouradShokralla;
            } else {
                // Pending tasks MUST have no assigned technician
                $status = TaskStatus::Pending;
                $assignedTech = null;
            }

            // Generate time slots: start hour (8-15) + duration (1-3 hours)
            $startHour = random_int(8, 15);
            $duration = random_int(1, 3);
            $endHour = $startHour + $duration;
            $timeSlotStart = sprintf('%02d:00', $startHour);
            $timeSlotEnd = sprintf('%02d:00', $endHour);

            // Generate scheduled date and completion date
            $scheduledDay = random_int(1, 20);
            $scheduledDate = Carbon::create(2026, 1, $scheduledDay)->toDateString();

            // For Approved/Completed tasks, set completion_date to scheduled_date + end_time
            if ($status === TaskStatus::Approved || $status === TaskStatus::Completed) {
                $completionDate = Carbon::create(2026, 1, $scheduledDay)->setTime($endHour, 0);
            } else {
                $completionDate = null;
            }

            $customer = $customers[random_int(0, 19)];
            $originalTech = random_int(0, 1) === 0 ? $techXC5 : $techXC1;

            $task = Task::create([
                'customer_id' => $customer->id,
                'original_tech_id' => $originalTech->id,
                'assigned_tech_id' => $assignedTech?->id,
                'task_type' => TaskType::ServiceCall,
                'status' => $status,
                'financial_status' => TaskFinancialStatus::Billable,
                'company_price' => (float) ($serviceCallPrice?->company_price ?? 0.00),
                'tech_price' => (float) ($serviceCallPrice?->tech_price ?? 0.00),
                'scheduled_date' => $scheduledDate,
                'time_slot_start' => $timeSlotStart,
                'time_slot_end' => $timeSlotEnd,
                'description' => "Service call for {$customer->name}. Issue: " . $this->getRandomIssue(),
                'completion_date' => $completionDate,
            ]);
            $tasksCreated[] = $task;

            if ($completionDate) {
                // ... TaskDetail logic
                TaskDetail::create([
                    'task_id' => $task->id,
                    'start_time_actual' => $completionDate->clone()->subHours(1),
                    'end_time_actual' => $completionDate,
                    'tech_notes' => $this->getRandomResolution(),
                    'start_lat' => $customer->lat,
                    'start_lng' => $customer->lng,
                    'end_lat' => $customer->lat,
                    'end_lng' => $customer->lng,
                ]);
            }
        }

        // ... (Summary Output remains same)
        $this->command->info('✅ Fixed Seeder executed successfully!');
    }

    private function getRandomIssue(): string
    {
        return collect(['Slow speed', 'No signal', 'Router broken'])->random();
    }

    private function getRandomResolution(): string
    {
        return collect(['Replaced router', 'Fixed splice', 'Reset config'])->random();
    }
}
