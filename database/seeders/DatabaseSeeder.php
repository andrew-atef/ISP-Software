<?php

namespace Database\Seeders;

use App\Enums\InventoryItemType;
use App\Enums\TaskFinancialStatus;
use App\Enums\TaskMediaType;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryWallet;
use App\Models\OriginalTech;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\TaskMedia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // =====================
        // USERS
        // =====================

        // Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'phone' => '+1234567890',
            'job_title' => 'System Administrator',
        ]);

        // Technician 1: Islam Youssef (Code: XC5)
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

        // Technician 2: Mourad Shokralla (Code: XC1)
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

        $techXC5 = OriginalTech::create([
            'name' => 'John Doe (XC5)',
            'code' => 'XC5',
        ]);

        $techXC1 = OriginalTech::create([
            'name' => 'Jane Smith (XC1)',
            'code' => 'XC1',
        ]);

        // =====================
        // INVENTORY ITEMS
        // =====================

        $eeroPro7 = InventoryItem::create([
            'name' => 'EERO Pro 7',
            'sku' => 'EERO-PRO-7',
            'type' => InventoryItemType::Indoor,
            'description' => 'High-performance mesh WiFi router for indoor use.',
        ]);

        $nokiaOnt = InventoryItem::create([
            'name' => 'Nokia ONT',
            'sku' => 'NOKIA-ONT-G1',
            'type' => InventoryItemType::Indoor,
            'description' => 'Optical Network Terminal for fiber connections.',
        ]);

        $fiberCable = InventoryItem::create([
            'name' => 'Fiber Cable (100m)',
            'sku' => 'FIBER-CBL-100M',
            'type' => InventoryItemType::Outdoor,
            'description' => '100 meter fiber optic cable roll.',
        ]);

        // =====================
        // INVENTORY WALLET (Stock for Islam Youssef)
        // =====================

        InventoryWallet::create([
            'user_id' => $islamYoussef->id,
            'inventory_item_id' => $eeroPro7->id,
            'quantity' => 10,
        ]);

        InventoryWallet::create([
            'user_id' => $islamYoussef->id,
            'inventory_item_id' => $nokiaOnt->id,
            'quantity' => 5,
        ]);

        InventoryWallet::create([
            'user_id' => $islamYoussef->id,
            'inventory_item_id' => $fiberCable->id,
            'quantity' => 3,
        ]);

        // =====================
        // CUSTOMERS
        // =====================

        // Customer in Orlando, Florida (for Google Maps test)
        $customer1 = Customer::create([
            'wire3_cid' => '1417419099',
            'name' => 'John Smith',
            'phone' => '+1407-555-1234',
            'address' => '123 Main Street, Orlando, FL 32801',
            'lat' => 28.538336,
            'lng' => -81.379234,
        ]);

        // Customer in Tampa, Florida
        $customer2 = Customer::create([
            'wire3_cid' => '1517418822',
            'name' => 'Maria Garcia',
            'phone' => '+1813-555-5678',
            'address' => '456 Oak Avenue, Tampa, FL 33602',
            'lat' => 27.950575,
            'lng' => -82.457176,
        ]);

        // =====================
        // TASKS
        // =====================

        // Scenario A: Completed New Install (Islam Youssef)
        $taskA = Task::create([
            'customer_id' => $customer1->id,
            'parent_task_id' => null,
            'original_tech_id' => $techXC5->id,
            'assigned_tech_id' => $islamYoussef->id,
            'task_type' => TaskType::NewInstall,
            'status' => TaskStatus::Completed,
            'financial_status' => TaskFinancialStatus::Billable,
            'company_price' => 350.00,
            'tech_price' => 145.00,
            'scheduled_date' => now()->subDays(2)->toDateString(),
            'time_slot_start' => '09:00',
            'time_slot_end' => '12:00',
            'saf_link' => 'https://wire3.com/saf/123456',
            'description' => 'New fiber installation for residential customer.',
            'import_batch_id' => 'BATCH-2026-01-10',
            'completion_date' => now()->subDays(2),
            'is_offline_sync' => true,
        ]);

        // TaskDetail for Task A
        TaskDetail::create([
            'task_id' => $taskA->id,
            'ont_serial' => 'NOKIA-ONT-SN-A1B2C3D4',
            'eero_serial_1' => 'EERO-SN-00001',
            'eero_serial_2' => 'EERO-SN-00002',
            'eero_serial_3' => null,
            'drop_bury_status' => true,
            'sidewalk_bore_status' => false,
            'start_time_actual' => now()->subDays(2)->setTime(9, 15),
            'end_time_actual' => now()->subDays(2)->setTime(11, 45),
            'tech_notes' => 'Installation went smoothly. Customer was very helpful. Cable buried along the side of the house.',
            'start_lat' => 28.538300,
            'start_lng' => -81.379200,
            'end_lat' => 28.538350,
            'end_lng' => -81.379250,
        ]);

        // TaskMedia for Task A
        TaskMedia::create([
            'task_id' => $taskA->id,
            'file_path' => 'https://placehold.co/600x400/22c55e/ffffff?text=Work+Photo',
            'type' => TaskMediaType::Work,
            'watermark_data' => json_encode([
                'lat' => 28.538336,
                'lng' => -81.379234,
                'timestamp' => now()->subDays(2)->setTime(10, 30)->toIso8601String(),
            ]),
            'taken_at' => now()->subDays(2)->setTime(10, 30),
        ]);

        TaskMedia::create([
            'task_id' => $taskA->id,
            'file_path' => 'https://placehold.co/600x400/f59e0b/ffffff?text=Bury+Photo',
            'type' => TaskMediaType::Bury,
            'watermark_data' => json_encode([
                'lat' => 28.538340,
                'lng' => -81.379240,
                'timestamp' => now()->subDays(2)->setTime(11, 15)->toIso8601String(),
            ]),
            'taken_at' => now()->subDays(2)->setTime(11, 15),
        ]);

        TaskMedia::create([
            'task_id' => $taskA->id,
            'file_path' => 'https://placehold.co/600x400/3b82f6/ffffff?text=Bore+Photo',
            'type' => TaskMediaType::Bore,
            'watermark_data' => json_encode([
                'lat' => 28.538345,
                'lng' => -81.379245,
                'timestamp' => now()->subDays(2)->setTime(11, 30)->toIso8601String(),
            ]),
            'taken_at' => now()->subDays(2)->setTime(11, 30),
        ]);

        // Scenario B: Pending Drop Bury (Mourad Shokralla)
        $taskB = Task::create([
            'customer_id' => $customer2->id,
            'parent_task_id' => null,
            'original_tech_id' => $techXC1->id,
            'assigned_tech_id' => $mouradShokralla->id,
            'task_type' => TaskType::DropBury,
            'status' => TaskStatus::Pending,
            'financial_status' => TaskFinancialStatus::NotBillable,
            'company_price' => 0.00,
            'tech_price' => 35.00,
            'scheduled_date' => now()->addDays(1)->toDateString(),
            'time_slot_start' => '14:00',
            'time_slot_end' => '17:00',
            'saf_link' => 'https://wire3.com/saf/789012',
            'description' => 'Drop bury for previous installation. Customer requested follow-up.',
            'import_batch_id' => 'BATCH-2026-01-12',
            'completion_date' => null,
            'is_offline_sync' => false,
        ]);

        // Additional Task: Service Call (for variety)
        Task::create([
            'customer_id' => $customer1->id,
            'parent_task_id' => null,
            'original_tech_id' => $techXC5->id,
            'assigned_tech_id' => $islamYoussef->id,
            'task_type' => TaskType::ServiceCall,
            'status' => TaskStatus::Assigned,
            'financial_status' => TaskFinancialStatus::Billable,
            'company_price' => 75.00,
            'tech_price' => 40.00,
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'time_slot_start' => '10:00',
            'time_slot_end' => '11:00',
            'saf_link' => null,
            'description' => 'Customer reports intermittent connection issues.',
            'import_batch_id' => null,
            'completion_date' => null,
            'is_offline_sync' => false,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('   Admin: admin@admin.com / password');
        $this->command->info('   Tech 1: islam@xconnect.com (XC5)');
        $this->command->info('   Tech 2: mourad@xconnect.com (XC1)');
    }
}
