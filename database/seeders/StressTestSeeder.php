<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StressTestSeeder extends Seeder
{
    /**
     * Faker instance
     */
    private $faker;

    /**
     * Default user count for stress testing
     */
    private const DEFAULT_USER_COUNT = 50;

    /**
     * Default company count for stress testing
     */
    private const DEFAULT_COMPANY_COUNT = 100;

    /**
     * Default contact count per company
     */
    private const DEFAULT_CONTACTS_PER_COMPANY = 3;

    /**
     * Default deal count
     */
    private const DEFAULT_DEAL_COUNT = 500;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->faker = Faker::create();

        $this->command->info('Starting Stress Test Data Seeder...');
        $this->command->info(str_repeat('-', 50));

        $this->ensureRolesExist();
        $this->createTeams();
        $this->createUsers();
        $this->createCompanies();
        $this->createContacts();
        $this->createDeals();

        $this->command->info(str_repeat('-', 50));
        $this->command->info('Stress Test Data Seeder completed!');
        $this->printSummary();
    }

    /**
     * Ensure required roles exist
     */
    private function ensureRolesExist(): void
    {
        $roles = ['admin', 'user'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $this->command->info('Roles ensured.');
    }

    /**
     * Create sales and compliance teams
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
     * Create users for stress testing
     */
    private function createUsers(): void
    {
        $count = self::DEFAULT_USER_COUNT;
        $salesTeam = Team::where('name', 'Sales Team')->first();
        $complianceTeam = Team::where('name', 'Compliance Team')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        $this->command->info("Creating {$count} users...");

        for ($i = 1; $i <= $count; $i++) {
            $isAdmin = $i <= 2; // First 2 are admins
            $isCompliance = $i > 2 && $i <= 5; // 3-5 are compliance
            // Rest are sales

            $user = User::firstOrCreate(
                ['email' => "stress_user_{$i}@test.com"],
                [
                    'name' => "Stress Test User {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign role
            if ($isAdmin) {
                $user->assignRole($adminRole);
            } else {
                $user->assignRole($userRole);
            }

            // Assign to team
            if ($isCompliance && $complianceTeam) {
                $user->teams()->syncWithoutDetaching([$complianceTeam->id]);
            } elseif ($salesTeam) {
                $user->teams()->syncWithoutDetaching([$salesTeam->id]);
            }
        }

        $this->command->info("{$count} users created successfully.");
    }

    /**
     * Create companies for stress testing
     */
    private function createCompanies(): void
    {
        $count = self::DEFAULT_COMPANY_COUNT;

        $this->command->info("Creating {$count} companies...");

        for ($i = 1; $i <= $count; $i++) {
            Company::firstOrCreate(
                ['name' => "Stress Test Company {$i}"],
                [
                    'email' => "company{$i}@".Str::random(8).'.test.com',
                    'domain' => "company{$i}.test.com",
                    'phone' => $this->faker->phoneNumber(),
                ]
            );
        }

        $this->command->info("{$count} companies created successfully.");
    }

    /**
     * Create contacts for stress testing
     */
    private function createContacts(): void
    {
        $contactsPerCompany = self::DEFAULT_CONTACTS_PER_COMPANY;
        $companies = Company::all();
        $totalContacts = $companies->count() * $contactsPerCompany;

        $this->command->info("Creating ~{$totalContacts} contacts...");

        $batchSize = 100;
        $created = 0;

        foreach ($companies as $company) {
            for ($i = 0; $i < $contactsPerCompany; $i++) {
                Contact::firstOrCreate(
                    [
                        'email' => "contact_{$company->id}_{$i}@test.com",
                    ],
                    [
                        'first_name' => $this->faker->firstName(),
                        'last_name' => $this->faker->lastName(),
                        'phone' => $this->faker->phoneNumber(),
                        'street_address' => $this->faker->streetAddress(),
                        'city' => $this->faker->city(),
                        'state' => $this->faker->state(),
                        'postal_code' => $this->faker->postcode(),
                        'country' => 'United Kingdom',
                        'ni_number' => strtoupper($this->faker->bothify('??######?')),
                        'bank' => $this->faker->company(),
                        'account_number' => $this->faker->numerify('########'),
                        'sort_code' => $this->faker->numerify('##-##-##'),
                        'date_of_birth' => $this->faker->date(),
                        'marital_status' => $this->faker->randomElement(['single', 'married', 'divorced', 'widowed']),
                        'gender' => $this->faker->randomElement(['male', 'female', 'other']),
                    ]
                );

                $created++;

                if ($created % $batchSize === 0) {
                    $this->command->info("  Created {$created} contacts...");
                }
            }
        }

        $this->command->info("{$created} contacts created successfully.");
    }

    /**
     * Create deals for stress testing
     */
    private function createDeals(): void
    {
        $count = self::DEFAULT_DEAL_COUNT;
        $users = User::all();
        $companies = Company::all();

        if ($users->isEmpty() || $companies->isEmpty()) {
            $this->command->error('Users or Companies table is empty. Seed them first.');

            return;
        }

        $this->command->info("Creating {$count} deals...");

        $batchSize = 100;

        for ($i = 1; $i <= $count; $i++) {
            $user = $users->random();
            $company = $companies->random();

            $deal = Deal::create([
                'name' => "Stress Test Deal {$i}",
                'amount' => $this->faker->numberBetween(5000, 50000),
                'stage' => $this->faker->randomElement(['doc sent', 'doc signed', 'compliant', 'ready for payment', 'paid']),
                'recruitment_agency' => $this->faker->randomElement(['Inbound', 'Referral']),
                'consultant_name' => $company->name,
                'agency_deal_value' => $this->faker->randomFloat(2, 1000, 25000),
                'margin_agreed' => $this->faker->randomFloat(2, 5, 40),
                'date_sent' => $this->faker->optional(0.6)->date(),
                'date_signed' => $this->faker->optional(0.4)->date(),
                'who_signed' => $this->faker->optional(0.4)->name(),
                'mda_setup' => $this->faker->boolean(30),
                'mda_reference_number' => $this->faker->optional(0.3)->bothify('MDA-####'),
                'date_set_up' => $this->faker->optional(0.3)->date(),
                'remittance_received' => $this->faker->boolean(40),
                'date_logged' => $this->faker->optional(0.8)->date(),
                'user_id' => $user->id,
                'starter_checklist_recieved_date' => $this->faker->optional(0.5)->date(),
                'starter_form' => $this->faker->boolean(60),
                'tax_code' => $this->faker->optional(0.5)->bothify('TAX-####'),
                'contract_recieved_date' => $this->faker->optional(0.4)->date(),
                'stage_updated_at' => now(),
            ]);

            // Attach company
            $deal->companies()->attach($company->id, ['is_primary' => true]);

            // Attach random contacts
            $contactCount = random_int(1, 3);
            $contacts = Contact::inRandomOrder()->limit($contactCount)->get();
            foreach ($contacts as $index => $contact) {
                $deal->contacts()->attach($contact->id, ['is_primary' => $index === 0]);
            }

            if ($i % $batchSize === 0) {
                $this->command->info("  Created {$i} deals...");
            }
        }

        $this->command->info("{$count} deals created successfully.");
    }

    /**
     * Print summary of created data
     */
    private function printSummary(): void
    {
        $this->command->info(str_repeat('=', 50));
        $this->command->info('STRESS TEST DATA SUMMARY:');
        $this->command->info(str_repeat('=', 50));

        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Users', User::count()],
                ['Teams', Team::count()],
                ['Companies', Company::count()],
                ['Contacts', Contact::count()],
                ['Deals', Deal::count()],
            ]
        );

        $this->command->info('Test user credentials:');
        $this->command->info('  Email: stress_user_1@test.com');
        $this->command->info('  Password: password');
    }
}
