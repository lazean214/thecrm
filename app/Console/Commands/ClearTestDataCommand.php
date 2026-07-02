<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('crm:clear-test-data')]
#[Description('Clear stress test data while preserving live users and teams')]
class ClearTestDataCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting test data cleanup...');
        $this->newLine();

        // Preserve these emails
        $preserveEmails = [
            'ncs.photo02@gmail.com',
            'test@example.com',
            'clyde92cedric@yahoo.com',
        ];

        // Count before deletion
        $counts = [
            'Users' => User::whereNotIn('email', $preserveEmails)->count(),
            'Companies' => Company::where('name', 'like', 'Stress Test Company %')->count(),
            'Contacts' => Contact::where('email', 'like', 'contact_%@test.com')->count(),
            'Deals' => Deal::where('name', 'like', 'Stress Test Deal %')->count(),
        ];

        $this->table(
            ['Type', 'Count to Delete'],
            collect($counts)->map(fn ($count, $type) => [$type, $count])->toArray()
        );

        if (! $this->confirm('Proceed with deletion?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Deleting test data...');

        // Delete stress test deals first (has relations)
        $deletedDeals = Deal::where('name', 'like', 'Stress Test Deal %')->delete();
        $this->info("  Deleted {$deletedDeals} deals");

        // Delete stress test contacts
        $deletedContacts = Contact::where('email', 'like', 'contact_%@test.com')->delete();
        $this->info("  Deleted {$deletedContacts} contacts");

        // Delete stress test companies
        $deletedCompanies = Company::where('name', 'like', 'Stress Test Company %')->delete();
        $this->info("  Deleted {$deletedCompanies} companies");

        // Delete stress test users
        $deletedUsers = User::whereNotIn('email', $preserveEmails)
            ->where('email', 'like', 'stress_user_%@test.com')
            ->delete();
        $this->info("  Deleted {$deletedUsers} users");

        // Clean up pivot tables for deleted deals
        DB::table('company_deal')->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('deals')
                ->whereColumn('deals.id', 'company_deal.deal_id');
        })->delete();

        DB::table('contact_deal')->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('deals')
                ->whereColumn('deals.id', 'contact_deal.deal_id');
        })->delete();

        $this->newLine();
        $this->info('Cleanup completed!');
        $this->newLine();

        // Print remaining data summary
        $this->table(
            ['Entity', 'Remaining Count'],
            [
                ['Users', User::count()],
                ['Teams', DB::table('teams')->count()],
                ['Companies', Company::count()],
                ['Contacts', Contact::count()],
                ['Deals', Deal::count()],
            ]
        );

        $this->newLine();
        $this->info('Preserved users:');
        foreach ($preserveEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $this->info("  - {$email}");
            }
        }

        return self::SUCCESS;
    }
}
