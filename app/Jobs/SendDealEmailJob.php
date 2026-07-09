<?php

namespace App\Jobs;

use App\Mail\DealEmailMailable;
use App\Models\DealEmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendDealEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $logId,
        public array $attachments = [],
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Idempotency check - skip if already processed
        if ($this->idempotencyKey && $this->alreadyProcessed()) {
            Log::info('SendDealEmailJob: Skipping duplicate job', [
                'log_id' => $this->logId,
                'idempotency_key' => $this->idempotencyKey,
            ]);

            return;
        }

        $log = DealEmailLog::find($this->logId);

        if (! $log) {
            Log::warning('SendDealEmailJob: Log not found', ['log_id' => $this->logId]);

            return;
        }

        // Skip if already sent
        if ($log->status === 'sent') {
            Log::info('SendDealEmailJob: Email already sent', ['log_id' => $this->logId]);

            return;
        }

        try {
            Mail::to($log->to_email)
                ->send(
                    new DealEmailMailable(
                        subjectLine: $log->subject,
                        bodyContent: $log->body,
                        emailAttachments: $this->attachments,
                    )
                );

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            // Cleanup temp attachments after successful send
            $this->cleanupTempAttachments();

            // Mark as processed for idempotency
            $this->markAsProcessed();

            Log::info('SendDealEmailJob: Email sent successfully', ['log_id' => $this->logId]);

        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('SendDealEmailJob: Email failed', [
                'log_id' => $this->logId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $log = DealEmailLog::find($this->logId);

        if ($log) {
            $log->update([
                'status' => 'failed',
                'error_message' => $exception?->getMessage() ?? 'Unknown error',
            ]);
        }

        Log::error('SendDealEmailJob: Job failed permanently', [
            'log_id' => $this->logId,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Check if this job was already processed (idempotency).
     */
    private function alreadyProcessed(): bool
    {
        if (! $this->idempotencyKey) {
            return false;
        }

        return Cache::has("email_sent:{$this->idempotencyKey}");
    }

    /**
     * Mark this job as processed for idempotency.
     */
    private function markAsProcessed(): void
    {
        if (! $this->idempotencyKey) {
            return;
        }

        // Cache for 24 hours to prevent duplicate sends
        Cache::put("email_sent:{$this->idempotencyKey}", true, now()->addHours(24));
    }

    /**
     * Cleanup temporary attachment files.
     */
    private function cleanupTempAttachments(): void
    {
        foreach ($this->attachments as $attachment) {
            if (! empty($attachment['path']) && str_contains($attachment['path'], 'email-temp-attachments')) {
                Storage::disk('local')->delete($attachment['path']);
            }
        }
    }
}
