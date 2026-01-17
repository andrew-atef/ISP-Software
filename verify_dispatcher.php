<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "=== DISPATCHER USER (Sarah Jenkins) ===\n";
$dispatcher = \App\Models\User::where('email', 'dispatch@xconnect.com')->first();

if ($dispatcher) {
    echo "Name: {$dispatcher->name}\n";
    echo "Email: {$dispatcher->email}\n";
    echo "Role Enum: {$dispatcher->role->value}\n";
    echo "Spatie Roles: " . implode(', ', $dispatcher->roles()->pluck('name')->toArray()) . "\n";
    echo "Permissions Count: " . $dispatcher->permissions()->count() . "\n\n";

    echo "=== PERMISSION CHECKS ===\n";
    echo "Can 'ViewAny:Task': " . ($dispatcher->can('ViewAny:Task') ? "YES ✅" : "NO ❌") . "\n";
    echo "Can 'Create:Task': " . ($dispatcher->can('Create:Task') ? "YES ✅" : "NO ❌") . "\n";
    echo "Can 'Update:Task': " . ($dispatcher->can('Update:Task') ? "YES ✅" : "NO ❌") . "\n";
    echo "Can 'Delete:Task': " . ($dispatcher->can('Delete:Task') ? "YES ✅" : "NO ❌") . "\n";
    echo "Can 'ViewAny:User': " . ($dispatcher->can('ViewAny:User') ? "YES ✅" : "NO ❌") . "\n";
    echo "Can 'Create:User': " . ($dispatcher->can('Create:User') ? "YES ✅" : "NO ❌") . "\n";
    echo "Can 'ViewAny:Payroll': " . ($dispatcher->can('ViewAny:Payroll') ? "YES ✅" : "NO ❌") . "\n";
    echo "Can 'View:DispatchCalendar': " . ($dispatcher->can('View:DispatchCalendar') ? "YES ✅" : "NO ❌") . "\n";
} else {
    echo "❌ Dispatcher user not found!\n";
}

echo "\n=== ALL USERS WITH ROLES ===\n";
$allUsers = \App\Models\User::with('roles')->get();
foreach ($allUsers as $user) {
    $roles = implode(', ', $user->roles()->pluck('name')->toArray() ?: ['NONE']);
    echo "{$user->name} ({$user->role->value}) -> Spatie: {$roles}\n";
}
