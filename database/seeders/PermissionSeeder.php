<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // CRM Permissions
        $permissions = [
            // Deals
            'view-deals',
            'create-deals',
            'edit-deals',
            'delete-deals',
            'move-deals',

            // Contacts
            'view-contacts',
            'create-contacts',
            'edit-contacts',
            'delete-contacts',
            'export-contacts',

            // Companies
            'view-companies',
            'create-companies',
            'edit-companies',
            'delete-companies',
            'import-companies',

            // Users
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Teams
            'view-teams',
            'create-teams',
            'edit-teams',
            'delete-teams',

            // Roles & Permissions
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'view-permissions',
            'create-permissions',
            'delete-permissions',

            // GDPR
            'manage-gdpr',
            'view-gdpr-dashboard',

            // Email
            'view-emails',
            'send-emails',

            // Reports
            'view-reports',
            'export-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        $salesRole = Role::where('name', 'sales')->first();
        $complianceRole = Role::where('name', 'compliance')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo(Permission::all());
        }

        if ($salesRole) {
            $salesRole->givePermissionTo([
                'view-deals',
                'create-deals',
                'edit-deals',
                'move-deals',
                'view-contacts',
                'create-contacts',
                'edit-contacts',
                'view-companies',
                'create-companies',
                'edit-companies',
                'view-emails',
                'send-emails',
                'view-reports',
                'export-contacts',
            ]);
        }

        if ($complianceRole) {
            $complianceRole->givePermissionTo([
                'view-deals',
                'edit-deals',
                'move-deals',
                'view-contacts',
                'edit-contacts',
                'export-contacts',
                'view-companies',
                'edit-companies',
                'manage-gdpr',
                'view-gdpr-dashboard',
                'view-emails',
                'send-emails',
                'view-reports',
            ]);
        }
    }
}
