<?php

namespace App\Console\Commands;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedPermissionsCommand extends Command
{
    protected $signature = 'app:seed-permissions {--fresh : Wipe all roles and permissions first}';

    protected $description = 'Seed roles and permissions without wiping existing data';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Wiping existing roles and permissions...');
            Permission::query()->delete();
            Role::query()->delete();
        }

        $this->info('Seeding roles and permissions...');
        $this->newLine();

        // Run the seeders
        $this->call('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);

        // Verify
        $roles = Role::count();
        $permissions = Permission::count();
        $admin = Role::where('name', 'admin')->first();
        $assignments = $admin ? $admin->permissions->count() : 0;

        $this->newLine();
        $this->info('Done!');
        $this->table(
            ['Item', 'Count'],
            [
                ['Roles', $roles],
                ['Permissions', $permissions],
                ['Admin permissions', $assignments],
            ]
        );

        return self::SUCCESS;
    }
}
