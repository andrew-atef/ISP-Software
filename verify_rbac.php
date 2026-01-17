<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

// Check super_admin role permissions
$superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
echo "=== SUPER ADMIN ROLE ===\n";
echo "✅ Total Permissions: " . $superAdminRole->permissions()->count() . "\n\n";

// List sample permissions
echo "Sample Permissions:\n";
$superAdminRole->permissions()->limit(10)->get()->each(function ($perm) {
    echo "  - " . $perm->name . "\n";
});
echo "  ... and " . ($superAdminRole->permissions()->count() - 10) . " more\n";

// Check admin user
echo "\n=== ADMIN USER ===\n";
$admin = \App\Models\User::first();
echo "User: {$admin->name} ({$admin->email})\n";
echo "User role enum: " . $admin->role->value . "\n";
echo "Spatie roles: " . implode(', ', $admin->roles()->pluck('name')->toArray()) . "\n";
echo "Has 'super_admin' role: " . ($admin->hasRole('super_admin') ? "YES ✅" : "NO ❌") . "\n";
echo "Can 'ViewAny:Task': " . ($admin->can('ViewAny:Task') ? "YES ✅" : "NO ❌") . "\n";

// Check all users and their roles
echo "\n=== ALL USERS ===\n";
$allUsers = \App\Models\User::all();
foreach ($allUsers as $user) {
    $rolesStr = implode(', ', $user->roles()->pluck('name')->toArray() ?: ['NONE']);
    echo "{$user->name} ({$user->role->value}) -> Spatie: {$rolesStr}\n";
}

// Check enum matching
echo "\n=== ENUM MATCHING ===\n";
$adminEnum = \App\Enums\UserRole::Admin;
echo "UserRole::Admin value: '{$adminEnum->value}'\n";

$queryResults = \App\Models\User::where('role', \App\Enums\UserRole::Admin)->get();
echo "Users matching UserRole::Admin: {$queryResults->count()}\n";
foreach ($queryResults as $user) {
    echo "  - {$user->name} (ID: {$user->id})\n";
}

// Try manually assigning roles
echo "\n=== MANUAL ROLE ASSIGNMENT ===\n";
$superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
foreach ($queryResults as $user) {
    if (!$user->hasRole('super_admin')) {
        $user->assignRole($superAdminRole);
        echo "Assigned super_admin to {$user->name}\n";
    }
}

// Verify after assignment
echo "\n=== VERIFICATION AFTER ASSIGNMENT ===\n";
$admin = \App\Models\User::find(1);
echo "Admin User roles: " . implode(', ', $admin->roles()->pluck('name')->toArray()) . "\n";
echo "Can 'ViewAny:Task': " . ($admin->can('ViewAny:Task') ? "YES ✅" : "NO ❌") . "\n";
