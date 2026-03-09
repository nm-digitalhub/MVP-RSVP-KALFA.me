<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        // 1. Create permissions (Permissions are GLOBAL in Spatie, Roles are Team-based)
        // Note: In Spatie Permission, permissions are shared across all teams.
        $permissions = [
            'manage-system',
            'manage-organizations',
            'manage-users',
            'impersonate-users',
            'view-event-details',
            'manage-event-guests',
            'manage-event-tables',
            'send-invitations',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 2. Create GLOBAL Role: Super Admin (assigned to organization_id = null)
        $registrar->setPermissionsTeamId(null);
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        // Give all permissions to Super Admin
        $superAdminRole->syncPermissions(Permission::all());

        // Assign global Super Admin role to existing system admins
        $admins = User::where('is_system_admin', true)->get();
        foreach ($admins as $admin) {
            $admin->assignRole($superAdminRole);
        }

        // 3. Create Organization-specific roles
        $organizations = Organization::all();
        foreach ($organizations as $org) {
            $registrar->setPermissionsTeamId($org->id);

            $orgAdmin = Role::findOrCreate('Organization Admin', 'web');
            $orgAdmin->syncPermissions([
                'view-event-details',
                'manage-event-guests',
                'manage-event-tables',
                'send-invitations',
            ]);

            $orgEditor = Role::findOrCreate('Organization Editor', 'web');
            $orgEditor->syncPermissions([
                'view-event-details',
                'manage-event-guests',
            ]);

            // Assign Org Admin to organization owner
            $owner = $org->owner();
            if ($owner) {
                $owner->assignRole($orgAdmin);
            }
        }

        $registrar->setPermissionsTeamId(null);
    }
}
