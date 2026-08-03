<?php

namespace App\Jobs;

use App\Models\Deal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Signable\App\Services\Signable\SignableClient;
use Throwable;

class SendSignableContractJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Deal $deal,
    ) {}

    public function handle(SignableClient $signable): void
    {
        $deal = $this->deal;

        Log::info('SendSignableContractJob: Processing', [
            'deal_id' => $deal->id,
            'deal_name' => $deal->name,
        ]);

        $contact = $deal->primaryContact();

        if (! $contact || ! $contact->email) {
            Log::warning('SendSignableContractJob: Deal has no primary contact with email', [
                'deal_id' => $deal->id,
            ]);

            return;
        }

        try {
            $payload = [
                'envelope_title' => "Contract: {$deal->name}",
                'envelope_parties' => [
                    [
                        'party_name' => $contact->first_name.' '.$contact->last_name,
                        'party_email' => $contact->email,
                        'party_role' => 'signer1',
                    ],
                ],
            ];

            $response = $signable->sendEnvelope($payload);

            if ($response->successful()) {
                $fingerprint = $response->json('envelope_fingerprint');

                $deal->signableEnvelopes()->create([
                    'envelope_fingerprint' => $fingerprint,
                    'title' => "Contract: {$deal->name}",
                    'status' => 'sent',
                    'queued_at' => now(),
                ]);

                Log::info('SendSignableContractJob: Envelope sent successfully', [
                    'deal_id' => $deal->id,
                    'fingerprint' => $fingerprint,
                ]);
            } else {
                Log::error('SendSignableContractJob: Signable API returned error', [
                    'deal_id' => $deal->id,
                    'response' => $response->json(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('SendSignableContractJob: Failed to send envelope', [
                'deal_id' => $deal->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('SendSignableContractJob: Failed permanently', [
            'deal_id' => $this->deal->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
