<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "=== CHECKING SUPER ADMIN WIDGET PERMISSIONS ===\n\n";

$admin = \App\Models\User::where('email', 'admin@admin.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

$allPermissions = $admin->getAllPermissions()->pluck('name')->sort()->values();
$widgetPermissions = $allPermissions->filter(fn($p) => str_contains($p, 'Widget'));

echo "Total Permissions: " . $allPermissions->count() . "\n";
echo "Widget Permissions: " . $widgetPermissions->count() . "\n\n";

if ($widgetPermissions->isEmpty()) {
    echo "❌ ERROR: Super Admin has NO Widget Permissions!\n";
    echo "\nExpected Widgets:\n";
    echo "  - View:StatsOverviewWidget\n";
    echo "  - View:LiveTechLocationWidget\n";
    echo "  - View:RevenueVsPayrollWidget\n";
} else {
    echo "✅ Super Admin Widget Permissions:\n";
    foreach ($widgetPermissions as $perm) {
        echo "  ✅ $perm\n";
    }
}

echo "\n=== BREAKDOWN BY CATEGORY ===\n\n";

// Resource permissions (11 actions × 9 resources = 99)
$resourcePerms = $allPermissions->filter(fn($p) =>
    preg_match('/^(ViewAny|View|Create|Update|Delete|Restore|ForceDelete|ForceDeleteAny|RestoreAny|Replicate|Reorder):(Task|User|Payroll|CompanyInvoice|JobPrice|OriginalTech|InventoryItem|InventoryRequest|Role)$/', $p)
);

// Page permissions
$pagePerms = $allPermissions->filter(fn($p) =>
    preg_match('/^View:(ProfitLossReport|DispatchCalendar|ReviewTasks|PendingPayrolls|TodayTasks|GeneratePayroll)$/', $p)
);

// Widget permissions
$widgetPerms = $allPermissions->filter(fn($p) =>
    str_contains($p, 'Widget')
);

echo "Resources: " . $resourcePerms->count() . " permissions\n";
echo "Pages: " . $pagePerms->count() . " permissions\n";
echo "Widgets: " . $widgetPerms->count() . " permissions\n";
echo "TOTAL: " . $allPermissions->count() . " permissions\n\n";

echo "=== WIDGET PERMISSIONS DETAIL ===\n\n";
foreach ($widgetPerms->sort() as $perm) {
    echo "  ✅ $perm\n";
}
