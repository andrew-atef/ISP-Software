<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "=== TEST: USER ROLE UPDATE ===\n\n";

// Get Islam Youssef (currently a technician)
$islam = \App\Models\User::where('email', 'islam@xconnect.com')->first();

echo "BEFORE UPDATE:\n";
echo "User: {$islam->name}\n";
echo "Enum Role: {$islam->role->value}\n";
echo "Spatie Roles: " . implode(', ', $islam->roles()->pluck('name')->toArray()) . "\n";
echo "Permissions: " . $islam->permissions()->count() . "\n";
echo "Can 'ViewAny:Task': " . ($islam->can('ViewAny:Task') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'View:DispatchCalendar': " . ($islam->can('View:DispatchCalendar') ? "YES ✅" : "NO ❌") . "\n";

// Update his role to Dispatcher
echo "\n--- UPDATING ROLE TO DISPATCHER ---\n";
$islam->update(['role' => \App\Enums\UserRole::Dispatch]);

// Refresh from database
$islam = $islam->fresh();

echo "\nAFTER UPDATE:\n";
echo "User: {$islam->name}\n";
echo "Enum Role: {$islam->role->value}\n";
echo "Spatie Roles: " . implode(', ', $islam->roles()->pluck('name')->toArray()) . "\n";
echo "Permissions: " . $islam->permissions()->count() . "\n";
echo "Can 'ViewAny:Task': " . ($islam->can('ViewAny:Task') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'View:DispatchCalendar': " . ($islam->can('View:DispatchCalendar') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'Create:Task': " . ($islam->can('Create:Task') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'ViewAny:User': " . ($islam->can('ViewAny:User') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'ViewAny:Payroll': " . ($islam->can('ViewAny:Payroll') ? "YES ✅" : "NO ❌") . "\n";

// Test 2: Change back to Tech
echo "\n--- UPDATING ROLE BACK TO TECHNICIAN ---\n";
$islam->update(['role' => \App\Enums\UserRole::Tech]);
$islam = $islam->fresh();

echo "\nAFTER UPDATE BACK TO TECH:\n";
echo "User: {$islam->name}\n";
echo "Enum Role: {$islam->role->value}\n";
echo "Spatie Roles: " . implode(', ', $islam->roles()->pluck('name')->toArray()) . "\n";
echo "Permissions: " . $islam->permissions()->count() . "\n";
echo "Can 'ViewAny:Task': " . ($islam->can('ViewAny:Task') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'View:DispatchCalendar': " . ($islam->can('View:DispatchCalendar') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'ViewAny:Payroll': " . ($islam->can('ViewAny:Payroll') ? "YES ✅" : "NO ❌") . "\n";

// Test 3: Change to Admin
echo "\n--- UPDATING ROLE TO ADMIN ---\n";
$islam->update(['role' => \App\Enums\UserRole::Admin]);
$islam = $islam->fresh();

echo "\nAFTER UPDATE TO ADMIN:\n";
echo "User: {$islam->name}\n";
echo "Enum Role: {$islam->role->value}\n";
echo "Spatie Roles: " . implode(', ', $islam->roles()->pluck('name')->toArray()) . "\n";
echo "Permissions: " . $islam->permissions()->count() . " (all 93)\n";
echo "Can 'ViewAny:Task': " . ($islam->can('ViewAny:Task') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'Delete:Task': " . ($islam->can('Delete:Task') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'View:ProfitLossReport': " . ($islam->can('View:ProfitLossReport') ? "YES ✅" : "NO ❌") . "\n";

// Reset back to Tech for next tests
$islam->update(['role' => \App\Enums\UserRole::Tech]);

echo "\n✅ Observer Sync Test Complete!\n";
