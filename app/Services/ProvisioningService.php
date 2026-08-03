<?php

namespace App\Services;

use App\Models\Deal;
use Illuminate\Support\Facades\Log;

class ProvisioningService
{
    public function provisionWorker(Deal $deal): void
    {
        $contact = $deal->primaryContact();
        if (! $contact) {
            Log::warning('ProvisioningService: Deal has no primary contact', ['deal_id' => $deal->id]);

            return;
        }

        Log::info('ProvisioningService: Worker provisioned locally', [
            'deal_id' => $deal->id,
        ]);
    }
}
