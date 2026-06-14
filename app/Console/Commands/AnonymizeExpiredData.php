<?php

namespace App\Console\Commands;

use App\Services\GdprRetentionService;
use Illuminate\Console\Command;

class AnonymizeExpiredData extends Command
{
    protected $signature = 'gdpr:anonymize-expired';

    protected $description = 'Anonymize personal data past retention period';

    public function handle(GdprRetentionService $retentionService)
    {
        $this->info('Starting GDPR data anonymization...');

        $anonymized = $retentionService->anonymizeExpiredContacts();
        $this->info("Anonymized {$anonymized} contacts.");

        $scheduled = $retentionService->scheduleSoftDeletionForInactiveUsers();
        $this->info("Marked {$scheduled} users for future deletion.");

        return Command::SUCCESS;
    }
}
