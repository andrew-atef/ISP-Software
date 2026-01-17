<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * RolesAndPermissionsSeeder
 *
 * Initializes the Spatie Laravel Permission RBAC system with strict role-based access control.
 *
 * PERMISSION NAMING CONVENTIONS:
 * - Resources: {action}:{resource} (e.g., ViewAny:Task, Create:Task)
 * - Pages: {action}:{page} (e.g., View:ProfitLossReport, View:DispatchCalendar)
 *
 * RESOURCES DEFINED:
 * - Task, User (Technician), Payroll, CompanyInvoice, JobPrice, OrigoryTech, InventoryItem, InventoryRequest
 *
 * PAGES DEFINED:
 * - ProfitLossReport, DispatchCalendar, ReviewTasks (QC Board), PendingPayrolls, TodayTasks
 *
 * ROLES STRUCTURE:
 * 1. super_admin → Maps to UserRole::Admin → Full system access
 * 2. dispatcher → Maps to UserRole::Dispatch → Limited access (Tasks, Users, Inventory, Calendar)
 * 3. technician → Maps to UserRole::Tech → Minimal access (PWA/API only, blocked from panel)
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================
        // 1. CREATE ALL PERMISSIONS
        // =====================================================

        $this->createAllPermissions();

        // =====================================================
        // 2. CREATE ROLES & ASSIGN PERMISSIONS
        // =====================================================

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $dispatcher = Role::firstOrCreate(['name' => 'dispatcher']);
        $technician = Role::firstOrCreate(['name' => 'technician']);

        // Super Admin: Grant ALL permissions explicitly
        $superAdmin->givePermissionTo(Permission::all());
        $this->command->info('✅ Assigned all permissions to super_admin');

        // Dispatcher: Specific permissions for limited workflow
        $this->grantDispatcherPermissions($dispatcher);

        // Technician: Minimal permissions (mostly API/PWA logic)
        $this->grantTechnicianPermissions($technician);

        // =====================================================
        // 3. SYNC EXISTING USERS TO ROLES
        // =====================================================

        $this->syncUsersToRoles($superAdmin, $dispatcher, $technician);

        $this->command->info('✅ Roles and Permissions seeded successfully!');
    }

    /**
     * Create all permissions for all resources and pages.
     */
    private function createAllPermissions(): void
    {
        $resources = [
            'Task' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'User' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'Payroll' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'CompanyInvoice' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'JobPrice' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'OriginalTech' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'InventoryItem' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'InventoryRequest' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
            'Role' => ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny', 'Replicate', 'Reorder'],
        ];

        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}:{$resource}"]);
            }
        }

        // Page permissions
        $pages = [
            'View:ProfitLossReport',
            'View:DispatchCalendar',
            'View:ReviewTasks',
            'View:PendingPayrolls',
            'View:TodayTasks',
            'View:GeneratePayroll',
        ];

        foreach ($pages as $page) {
            Permission::firstOrCreate(['name' => $page]);
        }

        // Widget permissions (Filament Shield Widgets tab)
        $widgets = [
            'View:DispatchCalendarWidget', // Dispatch calendar widget
            'View:ProfitLossStats', // Profit/Loss stats cards
            'View:ProfitLossTrendChart', // Profit/Loss trend chart
            'View:StatsOverviewWidget', // Keep if used on main dashboard
            'View:LiveTechLocationWidget', // Keep existing
            'View:RevenueVsPayrollWidget', // Keep existing if still referenced
        ];

        foreach ($widgets as $widget) {
            Permission::firstOrCreate(['name' => $widget]);
        }

        $this->command->info('✅ Created all permissions');
    }

    /**
     * Grant Dispatcher role permissions.
     *
     * DISPATCHER ACCESS:
     * ✅ Tasks: ViewAny, View, Create, Update (NO Delete)
     * ✅ Technicians (User): ViewAny, View (Read Only)
     * ✅ Inventory Requests: ViewAny, View, Update (for approval/receiving)
     * ✅ Inventory Items: ViewAny, View (Read Only)
     * ✅ Original Techs: ViewAny, View (Read Only)
     * ✅ Dispatch Calendar: View
     * ❌ Payroll: NO ACCESS
     * ❌ Company Invoices: NO ACCESS
     * ❌ Job Prices: NO ACCESS
     * ❌ Profit & Loss Report: NO ACCESS
     */
    private function grantDispatcherPermissions(Role $dispatcher): void
    {
        $dispatcherPermissions = [
            // TASKS: Full workflow (View, Create, Update) but NO Delete
            'ViewAny:Task',
            'View:Task',
            'Create:Task',
            'Update:Task',
            // NO 'Delete:Task' - Dispatchers cannot delete tasks

            // TECHNICIANS (User Resource): View Only
            'ViewAny:User',
            'View:User',
            // NO Create/Update/Delete - Dispatchers cannot manage users

            // INVENTORY REQUESTS: Approval/Receiving workflow
            'ViewAny:InventoryRequest',
            'View:InventoryRequest',
            'Create:InventoryRequest',
            'Update:InventoryRequest',
            // NO Delete - Dispatchers cannot delete requests

            // INVENTORY ITEMS: View Only (for reference)
            'ViewAny:InventoryItem',
            'View:InventoryItem',

            // ORIGINAL TECHS: View Only
            'ViewAny:OriginalTech',
            'View:OriginalTech',

            // PAGES: Dispatch Calendar + Task Dashboard
            'View:DispatchCalendar',
            // WIDGETS: Dispatch Calendar widget on dashboard
            'View:DispatchCalendarWidget',
            'View:TodayTasks',
            'View:ReviewTasks',

            // FINANCIAL: View Company Invoices and Payroll
            'ViewAny:CompanyInvoice',
            'View:CompanyInvoice',
            'ViewAny:Payroll',
            'View:Payroll',
        ];

        foreach ($dispatcherPermissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission]);
            $dispatcher->givePermissionTo($perm);
        }

        $this->command->info('✅ Assigned dispatcher permissions');
    }

    /**
     * Grant Technician role permissions.
     *
     * TECHNICIAN ACCESS:
     * Minimal permissions - mostly handled via PWA/API logic.
     * Technicians are blocked from Admin Panel via User::canAccessPanel().
     * These permissions are for API authorization only.
     */
    private function grantTechnicianPermissions(Role $technician): void
    {
        $technicianPermissions = [
            // INVENTORY ITEMS: View only (for mobile app reference)
            'ViewAny:InventoryItem',
            'View:InventoryItem',

            // INVENTORY REQUESTS: Create own (for requesting stock)
            'ViewAny:InventoryRequest',
            'View:InventoryRequest',
            'Create:InventoryRequest',
            // NO Update/Delete

            // PAYROLL: View only (own payroll via custom policy logic)
            'ViewAny:Payroll',
            'View:Payroll',
            // NO Create/Update/Delete
        ];

        foreach ($technicianPermissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission]);
            $technician->givePermissionTo($perm);
        }

        $this->command->info('✅ Assigned technician permissions');
    }

    /**
     * Sync existing users to their appropriate roles based on UserRole enum.
     *
     * Mapping:
     * - UserRole::Admin → super_admin
     * - UserRole::Dispatch → dispatcher
     * - UserRole::Tech → technician
     */
    private function syncUsersToRoles(Role $superAdmin, Role $dispatcher, Role $technician): void
    {
        // ADMINS → super_admin
        User::where('role', UserRole::Admin)->each(function ($user) use ($superAdmin) {
            if (!$user->hasRole('super_admin')) {
                $user->assignRole($superAdmin);
                $this->command->info("  ✅ Assigned 'super_admin' role to: {$user->name} ({$user->email})");
            }
        });

        // DISPATCHERS → dispatcher
        User::where('role', UserRole::Dispatch)->each(function ($user) use ($dispatcher) {
            if (!$user->hasRole('dispatcher')) {
                $user->assignRole($dispatcher);
                $this->command->info("  ✅ Assigned 'dispatcher' role to: {$user->name} ({$user->email})");
            }
        });

        // TECHNICIANS → technician
        User::where('role', UserRole::Tech)->each(function ($user) use ($technician) {
            if (!$user->hasRole('technician')) {
                $user->assignRole($technician);
                $this->command->info("  ✅ Assigned 'technician' role to: {$user->name} ({$user->email})");
            }
        });

        $this->command->info('✅ Synced all users to roles');
    }
}

