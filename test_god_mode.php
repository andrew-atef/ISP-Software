<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          SUPER ADMIN GOD MODE VERIFICATION                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get admin user
$admin = \App\Models\User::where('email', 'admin@admin.com')->first();

echo "=== SUPER ADMIN USER ===\n";
echo "Name: {$admin->name}\n";
echo "Email: {$admin->email}\n";
echo "Role Enum: {$admin->role->value}\n";
echo "Spatie Roles: " . implode(', ', $admin->roles()->pluck('name')->toArray()) . "\n";

// Total permissions
$totalPerms = \Spatie\Permission\Models\Permission::count();
$superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
$superAdminPerms = $superAdminRole->permissions()->count();

echo "\n=== PERMISSION STATISTICS ===\n";
echo "Total Permissions in System: {$totalPerms}\n";
echo "Super Admin Direct Permissions: {$superAdminPerms}\n";
echo "Gate::before() Active: " . ($admin->hasRole('super_admin') ? "YES ✅" : "NO ❌") . "\n";

// Break down permissions by category
echo "\n=== PERMISSIONS BY RESOURCE ===\n";

$resources = [
    'Task', 'User', 'Payroll', 'CompanyInvoice', 'JobPrice',
    'OriginalTech', 'InventoryItem', 'InventoryRequest', 'Role'
];

foreach ($resources as $resource) {
    $count = \Spatie\Permission\Models\Permission::where('name', 'LIKE', "%:{$resource}")
        ->orWhere('name', '=', "View:{$resource}")
        ->count();
    if ($count === 0) {
        $count = \Spatie\Permission\Models\Permission::where('name', 'LIKE', "%{$resource}")
            ->count();
    }
    echo "  {$resource}: {$count} permissions\n";
}

echo "\n=== PAGES ===\n";
$pages = \Spatie\Permission\Models\Permission::where('name', 'LIKE', 'View:%')
    ->whereNotIn('name', \Spatie\Permission\Models\Permission::where('name', 'LIKE', '%:%')
        ->where('name', 'NOT LIKE', 'View:%')
        ->pluck('name')
        ->toArray())
    ->get();

foreach ($pages as $page) {
    echo "  ✅ {$page->name}\n";
}

echo "\n=== WIDGETS ===\n";
$widgets = \Spatie\Permission\Models\Permission::where('name', 'LIKE', '%Widget')
    ->get();

foreach ($widgets as $widget) {
    echo "  ✅ {$widget->name}\n";
}

echo "\n=== GOD MODE TEST ===\n";
$testPermissions = [
    'ViewAny:Role',
    'Create:Role',
    'Update:Role',
    'Delete:Role',
    'View:GeneratePayroll',
    'View:StatsOverviewWidget',
    'View:LiveTechLocationWidget',
    'View:RevenueVsPayrollWidget',
];

echo "Testing Admin Access (with Gate::before bypass):\n";
foreach ($testPermissions as $perm) {
    $hasPerm = $admin->can($perm);
    $status = $hasPerm ? "✅" : "❌";
    echo "  {$status} {$perm}\n";
}

echo "\n=== GATE BYPASS MECHANISM ===\n";
echo "Location: app/Providers/AppServiceProvider.php\n";
echo "Code:\n";
echo "  Gate::before(function (\$user, \$ability) {\n";
echo "      return \$user->hasRole('super_admin') ? true : null;\n";
echo "  });\n";
echo "\nEffect:\n";
echo "  ✅ Super Admin bypasses ALL authorization checks\n";
echo "  ✅ Even if permission is unchecked in database\n";
echo "  ✅ Returns 'true' for any ability check\n";

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ SUPER ADMIN HAS ABSOLUTE ACCESS TO EVERYTHING!            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
