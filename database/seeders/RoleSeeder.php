<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define parent permissions
        $permissions = [
            ['name' => 'Dashboard', 'code' => 'DASHBOARD'],
            ['name' => 'Users', 'code' => 'USERS'],
            ['name' => 'Devices', 'code' => 'DEVICES'],
            ['name' => 'Groups', 'code' => 'GROUPS'],
            ['name' => 'Map', 'code' => 'MAP'],
            ['name' => 'Roles', 'code' => 'ROLES'],
            ['name' => 'System Logs', 'code' => 'SYSTEM_LOGS'],
            ['name' => 'Settings', 'code' => 'SETTINGS'],
            ['name' => 'Statistics', 'code' => 'STATISTICS'],
        ];

        // Insert or update parent permissions
        $parentPermissions = [];
        foreach ($permissions as $key => $permissionData) {
            $permission = Permission::updateOrCreate(
                ['code' => $permissionData['code']], // Match condition
                [
                    'name' => $permissionData['name'],
                    'parent_id' => null,
                    'order' => $key + 1,
                ]
            );
            $parentPermissions[$permissionData['code']] = $permission->id;
        }

        // Define child permissions
        $childPermissions = [
            ['name' => 'View Statistics', 'code' => 'VIEW_STATISTICS', 'parent' => 'STATISTICS'],
            ['name' => 'View Statistics by Month', 'code' => 'VIEW_STATISTICS_BY_MONTH', 'parent' => 'STATISTICS'],
            ['name' => 'View Users', 'code' => 'VIEW_USERS', 'parent' => 'USERS'],
            ['name' => 'Add User', 'code' => 'ADD_USER', 'parent' => 'USERS'],
            ['name' => 'Edit User', 'code' => 'EDIT_USER', 'parent' => 'USERS'],
            ['name' => 'Active User', 'code' => 'ACTIVE_USER', 'parent' => 'USERS'],
            ['name' => 'Delete User', 'code' => 'DELETE_USER', 'parent' => 'USERS'],
            ['name' => 'View Devices', 'code' => 'VIEW_DEVICES', 'parent' => 'DEVICES'],
            ['name' => 'Add Device', 'code' => 'ADD_DEVICE', 'parent' => 'DEVICES'],
            ['name' => 'Edit Device', 'code' => 'EDIT_DEVICE', 'parent' => 'DEVICES'],
            ['name' => 'Delete Device', 'code' => 'DELETE_DEVICE', 'parent' => 'DEVICES'],
            ['name' => 'Import Devices', 'code' => 'IMPORT_DEVICES', 'parent' => 'DEVICES'],
            ['name' => 'View Groups', 'code' => 'VIEW_GROUPS', 'parent' => 'GROUPS'],
            ['name' => 'Add Group', 'code' => 'ADD_GROUP', 'parent' => 'GROUPS'],
            ['name' => 'Edit Group', 'code' => 'EDIT_GROUP', 'parent' => 'GROUPS'],
            ['name' => 'Delete Group', 'code' => 'DELETE_GROUP', 'parent' => 'GROUPS'],
            ['name' => 'View Roles', 'code' => 'VIEW_ROLES', 'parent' => 'ROLES'],
            ['name' => 'Add Role', 'code' => 'ADD_ROLE', 'parent' => 'ROLES'],
            ['name' => 'Edit Role', 'code' => 'EDIT_ROLE', 'parent' => 'ROLES'],
            ['name' => 'Delete Role', 'code' => 'DELETE_ROLE', 'parent' => 'ROLES'],
            ['name' => 'View System Logs', 'code' => 'VIEW_SYSTEM_LOGS', 'parent' => 'SYSTEM_LOGS'],
            ['name' => 'View Settings', 'code' => 'VIEW_SETTINGS', 'parent' => 'SETTINGS'],
            ['name' => 'Edit Settings', 'code' => 'EDIT_SETTINGS', 'parent' => 'SETTINGS'],
            ['name' => 'View Map', 'code' => 'VIEW_MAP', 'parent' => 'MAP'],
        ];

        // Insert or update child permissions
        foreach ($childPermissions as $key => $child) {
            Permission::updateOrCreate(
                ['code' => $child['code']], // Match condition
                [
                    'name' => $child['name'],
                    'parent_id' => $parentPermissions[$child['parent']],
                    'order' => $key + 1,
                ]
            );
        }

        // Create or update the admin role
        $adminRole = Role::updateOrCreate(
            ['name' => 'Admin'], // Match condition
            [
                'is_delete' => false,
                'insert_user_id' => null,
                'update_user_id' => null,
                'delete_user_id' => null,
                'delete_date' => null,
            ]
        );

        // Assign all permissions to the admin role
        $adminRole->permissions()->sync(Permission::pluck('id')->toArray());

        // Create or update the admin user
        User::updateOrCreate(
            ['personal_email' => 'admin@admin.com'], // Match condition
            [
                'first_name' => 'Admin',
                'middle_name' => 'Super',
                'last_name' => 'User',
                'company_email' => 'admin@company.com',
                'phone' => '1234567890',
                'address' => 'Admin Street',
                'password' => Hash::make('123456'),
                'marital_status' => 'Single',
                'image' => null,
                'role_id' => $adminRole->id,
                'receives_emails' => true,
                'last_email_sent' => null,
                'email_frequency_hours' => 0,
                'is_logout' => false,
                'is_active' => true,
                'is_delete' => false,
                'insert_user_id' => null,
                'update_user_id' => null,
                'delete_user_id' => null,
                'delete_date' => null,
            ]
        );
    }
}
