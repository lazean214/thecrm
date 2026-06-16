<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StressTestSeeder extends Seeder
{
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
     * Sample first names
     */
    private const FIRST_NAMES = [
        'James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda',
        'William', 'Elizabeth', 'David', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica',
        'Thomas', 'Sarah', 'Charles', 'Karen', 'Christopher', 'Nancy', 'Daniel', 'Lisa',
    ];

    /**
     * Sample last names
     */
    private const LAST_NAMES = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas',
        'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris',
    ];

    /**
     * Sample cities
     */
    private const CITIES = [
        'London', 'Manchester', 'Birmingham', 'Leeds', 'Glasgow', 'Liverpool', 'Newcastle',
        'Sheffield', 'Bristol', 'Edinburgh', 'Cardiff', 'Belfast', 'Nottingham', 'Southampton',
    ];

    /**
     * Sample street names
     */
    private const STREETS = [
        'High Street', 'Main Street', 'Church Road', 'Park Lane', 'Station Road',
        'Mill Road', 'School Lane', 'Victoria Road', 'Manchester Road', 'Church Street',
    ];

    /**
     * Sample UK banks
     */
    private const BANKS = [
        'Barclays Bank', 'HSBC', 'Lloyds Bank', 'NatWest', 'Santander UK',
        ' Halifax', 'Metro Bank', 'First Direct', 'Monzo', 'Starling Bank',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            $isAdmin = $i <= 2;
            $isCompliance = $i > 2 && $i <= 5;

            $user = User::firstOrCreate(
                ['email' => "stress_user_{$i}@test.com"],
                [
                    'name' => "Stress Test User {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if ($isAdmin) {
                $user->assignRole($adminRole);
            } else {
                $user->assignRole($userRole);
            }

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
                    'phone' => $this->randomPhone(),
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
                $created++;

                Contact::firstOrCreate(
                    [
                        'email' => "contact_{$company->id}_{$i}@test.com",
                    ],
                    [
                        'first_name' => $this->randomFirstName(),
                        'last_name' => $this->randomLastName(),
                        'phone' => $this->randomPhone(),
                        'street_address' => $this->randomStreet(),
                        'city' => $this->randomCity(),
                        'state' => $this->randomCity(),
                        'postal_code' => $this->randomPostcode(),
                        'country' => 'United Kingdom',
                        'ni_number' => $this->randomNiNumber(),
                        'bank' => $this->randomBank(),
                        'account_number' => $this->randomAccountNumber(),
                        'sort_code' => $this->randomSortCode(),
                        'date_of_birth' => $this->randomDate(),
                        'marital_status' => $this->randomMaritalStatus(),
                        'gender' => $this->randomGender(),
                    ]
                );

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

        $stages = ['doc sent', 'doc signed', 'compliant', 'ready for payment', 'paid'];
        $agencies = ['Inbound', 'Referral'];
        $batchSize = 100;

        for ($i = 1; $i <= $count; $i++) {
            $user = $users->random();
            $company = $companies->random();

            $deal = Deal::create([
                'name' => "Stress Test Deal {$i}",
                'amount' => $this->randomAmount(),
                'stage' => $stages[array_rand($stages)],
                'recruitment_agency' => $agencies[array_rand($agencies)],
                'consultant_name' => $company->name,
                'agency_deal_value' => $this->randomAgencyValue(),
                'margin_agreed' => $this->randomMargin(),
                'date_sent' => $this->maybe($this->randomDate()),
                'date_signed' => $this->maybe($this->randomDate()),
                'who_signed' => $this->maybe($this->randomFirstName().' '.$this->randomLastName()),
                'mda_setup' => $this->maybe(true, 30),
                'mda_reference_number' => $this->maybe('MDA-'.rand(1000, 9999)),
                'date_set_up' => $this->maybe($this->randomDate()),
                'remittance_received' => $this->maybe(true, 40),
                'date_logged' => $this->maybe($this->randomDate()),
                'user_id' => $user->id,
                'starter_checklist_recieved_date' => $this->maybe($this->randomDate()),
                'starter_form' => $this->maybe(true, 60),
                'tax_code' => $this->maybe('TAX-'.rand(1000, 9999)),
                'contract_recieved_date' => $this->maybe($this->randomDate()),
                'stage_updated_at' => now(),
            ]);

            // Attach company
            $deal->companies()->attach($company->id, ['is_primary' => true]);

            // Attach random contacts
            $contactCount = rand(1, 3);
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

    /*
    |--------------------------------------------------------------------------
    | Random Data Helper Methods
    |--------------------------------------------------------------------------
    */

    private function randomFirstName(): string
    {
        return self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
    }

    private function randomLastName(): string
    {
        return self::LAST_NAMES[array_rand(self::LAST_NAMES)];
    }

    private function randomCity(): string
    {
        return self::CITIES[array_rand(self::CITIES)];
    }

    private function randomStreet(): string
    {
        return rand(1, 999).' '.self::STREETS[array_rand(self::STREETS)];
    }

    private function randomPhone(): string
    {
        return '07'.rand(100000000, 999999999);
    }

    private function randomPostcode(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $outcode = chr(rand(65, 90)).chr(rand(65, 90)).rand(1, 9);

        return $outcode.' '.rand(1, 99).$letters[rand(0, 25)].$letters[rand(0, 25)];
    }

    private function randomNiNumber(): string
    {
        return strtoupper(chr(rand(65, 90)).chr(rand(65, 90))).rand(100000, 999999).chr(rand(65, 90));
    }

    private function randomBank(): string
    {
        return self::BANKS[array_rand(self::BANKS)];
    }

    private function randomAccountNumber(): string
    {
        return str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    }

    private function randomSortCode(): string
    {
        return rand(10, 99).'-'.rand(10, 99).'-'.rand(10, 99);
    }

    private function randomDate(): string
    {
        return date('Y-m-d', strtotime('-'.rand(1, 3650).' days'));
    }

    private function randomMaritalStatus(): string
    {
        return ['single', 'married', 'divorced', 'widowed'][array_rand(['single', 'married', 'divorced', 'widowed'])];
    }

    private function randomGender(): string
    {
        return ['male', 'female', 'other'][array_rand(['male', 'female', 'other'])];
    }

    private function randomAmount(): int
    {
        return rand(5000, 50000);
    }

    private function randomAgencyValue(): float
    {
        return round(rand(1000, 25000) + rand(0, 99) / 100, 2);
    }

    private function randomMargin(): float
    {
        return round(rand(500, 4000) / 100, 2);
    }

    /**
     * Return value or null based on probability
     */
    private function maybe($value, int $probability = 60): mixed
    {
        return rand(1, 100) <= $probability ? $value : null;
    }
}
