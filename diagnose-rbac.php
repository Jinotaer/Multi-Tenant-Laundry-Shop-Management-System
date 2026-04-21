<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== RBAC Diagnostic Report ===\n\n";

// Get tenant
$tenant = App\Models\Tenant::first();
if (!$tenant) {
    echo "❌ No tenant found!\n";
    exit;
}

tenancy()->initialize($tenant);
echo "Tenant: {$tenant->id}\n\n";

// Ensure permissions exist
App\Models\Permission::ensureDefaultsExist();

// Check database structure
echo "1. Database Structure:\n";
$tables = ['roles', 'role_user', 'permissions', 'permission_role', 'user_permissions'];
foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo "   " . ($exists ? "✓" : "✗") . " {$table}\n";
}

// Check role_user constraints
echo "\n2. role_user Table Structure:\n";
$indexes = DB::select("SHOW INDEXES FROM role_user");
foreach ($indexes as $index) {
    if ($index->Key_name !== 'PRIMARY') {
        echo "   - {$index->Key_name} on {$index->Column_name} (Unique: " . ($index->Non_unique ? 'NO' : 'YES') . ")\n";
    }
}

// Check roles
echo "\n3. Available Roles:\n";
$roles = App\Models\Role::with('permissions')->get();
foreach ($roles as $role) {
    echo "   - {$role->slug}: {$role->name} ({$role->permissions->count()} permissions)\n";
}

// Check users
echo "\n4. Users:\n";
$users = App\Models\User::with('roles')->get();
foreach ($users as $user) {
    echo "   - {$user->name} ({$user->email})\n";
    echo "     Role column: {$user->role}\n";
    echo "     Assigned roles: " . $user->roles->pluck('slug')->implode(', ') . "\n";
    
    // Check if user has any custom roles
    $customRoles = $user->roles->whereNotIn('slug', ['owner', 'staff', 'customer']);
    if ($customRoles->isNotEmpty()) {
        echo "     Custom roles: " . $customRoles->pluck('slug')->implode(', ') . "\n";
        foreach ($customRoles as $customRole) {
            echo "       → Permissions from {$customRole->slug}: " . $customRole->permissions->pluck('key')->implode(', ') . "\n";
        }
    }
    
    // Test some permissions
    if ($user->role === 'staff') {
        echo "     Permission tests:\n";
        echo "       - customers.view: " . ($user->hasPermission('customers.view') ? '✓' : '✗') . "\n";
        echo "       - customers.create: " . ($user->hasPermission('customers.create') ? '✓' : '✗') . "\n";
        echo "       - staff.view: " . ($user->hasPermission('staff.view') ? '✓' : '✗') . "\n";
    }
    echo "\n";
}

// Check role_user pivot data
echo "5. Role-User Assignments (role_user table):\n";
$pivots = DB::table('role_user')
    ->join('users', 'role_user.user_id', '=', 'users.id')
    ->join('roles', 'role_user.role_id', '=', 'roles.id')
    ->select('users.name', 'users.email', 'roles.slug as role_slug')
    ->get();

foreach ($pivots as $pivot) {
    echo "   - {$pivot->name} → {$pivot->role_slug}\n";
}

echo "\n=== End of Report ===\n";
