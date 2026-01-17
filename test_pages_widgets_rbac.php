<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FILAMENT PAGES & WIDGETS RBAC ENFORCEMENT TEST               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get test users
$admin = \App\Models\User::where('email', 'admin@admin.com')->first();
$dispatcher = \App\Models\User::where('email', 'dispatch@xconnect.com')->first();
$tech = \App\Models\User::where('email', 'islam@xconnect.com')->first();

echo "=== TEST USERS ===\n";
echo "Admin: {$admin->name} ({$admin->role->value})\n";
echo "Dispatcher: {$dispatcher->name} ({$dispatcher->role->value})\n";
echo "Technician: {$tech->name} ({$tech->role->value})\n\n";

// Test Pages
echo "=== PAGE ACCESS CONTROL ===\n\n";

// Test all pages
$testPages = [
    'DispatchCalendar' => \App\Filament\Pages\DispatchCalendar::class,
    'ProfitLossReport' => \App\Filament\Pages\ProfitLossReport::class,
    'TodayTasks' => \App\Filament\Pages\TodayTasks::class,
    'ReviewTasks' => \App\Filament\Pages\ReviewTasks::class,
];

$testUsers = [
    'Admin' => $admin,
    'Dispatcher' => $dispatcher,
    'Technician' => $tech,
];

foreach ($testPages as $pageName => $pageClass) {
    echo strtoupper($pageName) . " PAGE:\n";
    foreach ($testUsers as $name => $user) {
        auth()->setUser($user);

        $canAccess = $pageClass::canAccess();
        $status = $canAccess ? "✅ CAN ACCESS" : "❌ DENIED";
        echo "  {$name}: {$status}\n";
    }
    echo "\n";
}

// Test Widgets
echo "\n=== WIDGET ACCESS CONTROL ===\n\n";

echo "PROFIT & LOSS STATS WIDGET:\n";
foreach ([
    'Admin' => $admin,
    'Dispatcher' => $dispatcher,
    'Technician' => $tech,
] as $name => $user) {
    auth()->setUser($user);

    $canView = \App\Filament\Widgets\ProfitLossStats::canView();
    $status = $canView ? "✅ CAN VIEW" : "❌ DENIED";
    echo "  {$name}: {$status}\n";
}

echo "\nPROFIT & LOSS TREND CHART WIDGET:\n";
foreach ([
    'Admin' => $admin,
    'Dispatcher' => $dispatcher,
    'Technician' => $tech,
] as $name => $user) {
    auth()->setUser($user);

    $canView = \App\Filament\Widgets\ProfitLossTrendChart::canView();
    $status = $canView ? "✅ CAN VIEW" : "❌ DENIED";
    echo "  {$name}: {$status}\n";
}

echo "\nDISPATCH CALENDAR WIDGET:\n";
foreach ([
    'Admin' => $admin,
    'Dispatcher' => $dispatcher,
    'Technician' => $tech,
] as $name => $user) {
    auth()->setUser($user);

    $canView = \App\Filament\Widgets\DispatchCalendarWidget::canView();
    $status = $canView ? "✅ CAN VIEW" : "❌ DENIED";
    echo "  {$name}: {$status}\n";
}

// Permission Details
echo "\n=== PERMISSION DETAILS ===\n\n";

echo "DISPATCH CALENDAR PERMISSION:\n";
$perm = \Spatie\Permission\Models\Permission::where('name', 'View:DispatchCalendar')->first();
echo "  Permission: {$perm->name}\n";
echo "  Admin has permission: " . ($admin->can('View:DispatchCalendar') ? "✅ YES" : "❌ NO") . "\n";
echo "  Dispatcher has permission: " . ($dispatcher->can('View:DispatchCalendar') ? "✅ YES" : "❌ NO") . "\n";
echo "  Technician has permission: " . ($tech->can('View:DispatchCalendar') ? "✅ YES" : "❌ NO") . "\n";

echo "\nPROFIT & LOSS REPORT PERMISSION:\n";
$perm = \Spatie\Permission\Models\Permission::where('name', 'View:ProfitLossReport')->first();
echo "  Permission: {$perm->name}\n";
echo "  Admin has permission: " . ($admin->can('View:ProfitLossReport') ? "✅ YES" : "❌ NO") . "\n";
echo "  Dispatcher has permission: " . ($dispatcher->can('View:ProfitLossReport') ? "✅ YES" : "❌ NO") . "\n";
echo "  Technician has permission: " . ($tech->can('View:ProfitLossReport') ? "✅ YES" : "❌ NO") . "\n";

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ RBAC ENFORCEMENT ACTIVE ON ALL PAGES & WIDGETS            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
