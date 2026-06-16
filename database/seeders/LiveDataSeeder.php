<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LiveDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the essential live users and teams for production use.
     * Safe to run on existing database - uses firstOrCreate to avoid duplicates.
     */
    public function run(): void
    {
        $this->command->info('Starting Live Data Seeder...');
        $this->command->info(str_repeat('-', 50));

        $this->ensureRolesExist();
        $this->createTeams();
        $this->createUsers();

        $this->command->info(str_repeat('-', 50));
        $this->command->info('Live Data Seeder completed!');
        $this->printSummary();
    }

    /**
     * Ensure required roles exist
     */
    private function ensureRolesExist(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $this->command->info('Roles ensured: admin, user');
    }

    /**
     * Create teams
     */
    private function createTeams(): void
    {
        $teams = [
            ['name' => 'Sales Team', 'description' => 'Sales department team'],
            ['name' => 'Compliance Team', 'description' => 'Compliance department team'],
        ];

        foreach ($teams as $teamData) {
            Team::firstOrCreate(['name' => $teamData['name']], $teamData);
        }

        $this->command->info('Teams created: Sales Team, Compliance Team');
    }

    /**
     * Create the essential users for live data
     */
    private function createUsers(): void
    {
        $salesTeam = Team::where('name', 'Sales Team')->first();
        $complianceTeam = Team::where('name', 'Compliance Team')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        // Administrator user
        $admin = User::firstOrCreate(
            ['email' => 'ncs.photo02@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Administrator'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole($adminRole);
        $this->command->info('Created: ncs.photo02@gmail.com (Administrator - admin role)');

        // Compliance Team user
        $compliance = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Compliance User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $compliance->assignRole($userRole);
        if ($complianceTeam) {
            $compliance->teams()->syncWithoutDetaching([$complianceTeam->id]);
        }
        $this->command->info('Created: test@example.com (Compliance Team)');

        // Sales Team user
        $sales = User::firstOrCreate(
            ['email' => 'clyde92cedric@yahoo.com'],
            [
                'name' => 'Clyde Cedric',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $sales->assignRole($userRole);
        if ($salesTeam) {
            $sales->teams()->syncWithoutDetaching([$salesTeam->id]);
        }
        $this->command->info('Created: clyde92cedric@yahoo.com (Sales Team)');
    }

    /**
     * Print summary of created data
     */
    private function printSummary(): void
    {
        $this->command->info(str_repeat('=', 50));
        $this->command->info('LIVE DATA SUMMARY:');
        $this->command->info(str_repeat('=', 50));

        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Users', User::count()],
                ['Teams', Team::count()],
            ]
        );

        $this->command->info('User credentials:');
        $this->command->info('  Admin: ncs.photo02@gmail.com / Administrator');
        $this->command->info('  Compliance: test@example.com / password');
        $this->command->info('  Sales: clyde92cedric@yahoo.com / password');
    }
}
