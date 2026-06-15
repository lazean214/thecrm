<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Create teams
        $salesTeam = Team::firstOrCreate(
            ['name' => 'Sales Team'],
            ['description' => 'Sales team members — can manage deals up to Compliant stage.'],
        );

        $complianceTeam = Team::firstOrCreate(
            ['name' => 'Compliance Team'],
            ['description' => 'Compliance team members — full access to all deal stages.'],
        );

        // Assign roles to existing users based on their team
        $salesRole = Role::where('name', 'sales')->first();
        $complianceRole = Role::where('name', 'compliance')->first();

        if ($salesRole) {
            User::whereHas('teams', fn ($q) => $q->where('name', 'Sales Team'))
                ->each(fn ($user) => $user->assignRole($salesRole));
        }

        if ($complianceRole) {
            User::whereHas('teams', fn ($q) => $q->where('name', 'Compliance Team'))
                ->each(fn ($user) => $user->assignRole($complianceRole));
        }
    }
}
