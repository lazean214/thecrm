<?php

namespace Modules\Signable\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Signable\App\Http\Requests\SendEnvelopeRequest;
use Modules\Signable\App\Http\Requests\StoreTemplateEnvelopeRequest;
use Modules\Signable\App\Http\Requests\UpdateEnvelopePartyRequest;
use Modules\Signable\App\Models\DealSignableEnvelope;
use Modules\Signable\App\Services\Signable\SignableClient;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SignableEnvelopeController extends Controller
{
    public function __construct(private readonly SignableClient $client) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $response = $this->client->listEnvelopes($request->only(['offset', 'limit', 'status', 'search', 'date_from', 'date_to']));
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function store(SendEnvelopeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $dealId = (int) ($validated['deal_id'] ?? 0);
        unset($validated['deal_id']);

        try {
            $response = $this->client->sendEnvelope($validated);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        if ($dealId > 0 && $response->successful()) {
            $this->persistDealEnvelope($dealId, $validated, $response->json() ?? []);
        }

        return $this->proxy($response, 202);
    }

    public function dealEnvelopes(Request $request, Deal $deal): JsonResponse
    {
        if ($request->boolean('sync')) {
            $this->syncDealEnvelopeRecordsFromApi($deal);
        }

        $envelopes = DealSignableEnvelope::query()
            ->where('deal_id', $deal->id)
            ->orderByDesc('date_created')
            ->orderByDesc('id')
            ->get([
                'id',
                'deal_id',
                'user_id',
                'envelope_title',
                'envelope_fingerprint',
                'date_created',
                'date_signed',
                'envelope_status',
                'download_link',
            ])
            ->map(function (DealSignableEnvelope $envelope): array {
                return [
                    'id' => $envelope->id,
                    'deal_id' => $envelope->deal_id,
                    'user_id' => $envelope->user_id,
                    'envelope_title' => $envelope->envelope_title,
                    'envelope_fingerprint' => $envelope->envelope_fingerprint,
                    'envelope_created' => optional($envelope->date_created)?->toIso8601String(),
                    'envelope_processed' => optional($envelope->date_signed)?->toIso8601String(),
                    'envelope_status' => $envelope->envelope_status,
                    'download_link' => $envelope->download_link,
                ];
            })
            ->values();

        return response()->json([
            'data' => $envelopes,
        ]);
    }

    private function syncDealEnvelopeRecordsFromApi(Deal $deal): void
    {
        $terminalStatuses = [
            'signed',
            'completed',
            'cancelled',
            'expired',
            'declined',
            'voided',
            'rejected',
            'failed',
        ];

        $storedEnvelopes = DealSignableEnvelope::query()
            ->where('deal_id', $deal->id)
            ->where(function ($query) use ($terminalStatuses): void {
                $query->whereNull('envelope_status')
                    ->orWhereNotIn('envelope_status', $terminalStatuses);
            })
            ->get(['deal_id', 'envelope_fingerprint', 'user_id', 'envelope_title']);

        foreach ($storedEnvelopes as $storedEnvelope) {
            try {
                $response = $this->client->getEnvelope((string) $storedEnvelope->envelope_fingerprint);
            } catch (Throwable) {
                continue;
            }

            if ($response->failed()) {
                continue;
            }

            $this->persistDealEnvelope(
                (int) $storedEnvelope->deal_id,
                [
                    'user_id' => (int) $storedEnvelope->user_id,
                    'envelope_title' => (string) $storedEnvelope->envelope_title,
                ],
                $response->json() ?? []
            );
        }
    }

    public function show(string $fingerprint): JsonResponse
    {
        try {
            $response = $this->client->getEnvelope($fingerprint);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function destroy(string $fingerprint): JsonResponse
    {
        try {
            $response = $this->client->deleteEnvelope($fingerprint);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function remind(string $fingerprint): JsonResponse
    {
        try {
            $response = $this->client->remindEnvelope($fingerprint);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function cancel(string $fingerprint): JsonResponse
    {
        try {
            $response = $this->client->cancelEnvelope($fingerprint);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function expire(string $fingerprint): JsonResponse
    {
        try {
            $response = $this->client->expireEnvelope($fingerprint);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function updateParty(UpdateEnvelopePartyRequest $request, string $fingerprint, string $partyId): JsonResponse
    {
        try {
            $response = $this->client->updateEnvelopeParty($fingerprint, $partyId, $request->validated());
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function batchDownload(Request $request): BinaryFileResponse|JsonResponse
    {
        if (! class_exists(\ZipArchive::class)) {
            return response()->json([
                'message' => 'ZIP support is not available on this server.',
            ], 500);
        }

        $validated = $request->validate([
            'fingerprints' => ['required', 'array', 'min:1'],
            'fingerprints.*' => ['required', 'string'],
        ]);

        $fingerprints = collect($validated['fingerprints'])
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values();

        if ($fingerprints->isEmpty()) {
            return response()->json([
                'message' => 'No valid envelope fingerprints were provided.',
            ], 422);
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'signable_envelopes_');
        if ($zipPath === false) {
            return response()->json([
                'message' => 'Failed to create temporary ZIP file.',
            ], 500);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($zipPath);

            return response()->json([
                'message' => 'Unable to create ZIP archive.',
            ], 500);
        }

        $added = 0;

        foreach ($fingerprints as $index => $fingerprint) {
            try {
                $envelopeResponse = $this->client->getEnvelope($fingerprint);
            } catch (Throwable) {
                continue;
            }

            if ($envelopeResponse->failed()) {
                continue;
            }

            $envelopePayload = $envelopeResponse->json() ?? [];
            $downloadUrl = $this->findDownloadUrl($envelopePayload);

            if (! is_string($downloadUrl) || $downloadUrl === '') {
                continue;
            }

            try {
                $fileResponse = Http::timeout(90)->get($downloadUrl);
            } catch (Throwable) {
                continue;
            }

            if ($fileResponse->failed()) {
                continue;
            }

            $entryName = $this->buildZipEntryName($envelopePayload, $fingerprint, (int) $index + 1, $fileResponse);
            if (! $zip->addFromString($entryName, $fileResponse->body())) {
                continue;
            }

            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);

            return response()->json([
                'message' => 'No downloadable files were found for the selected envelopes.',
            ], 422);
        }

        $timestamp = now()->format('Ymd_His');
        $downloadName = "envelopes_{$timestamp}.zip";

        return response()->download($zipPath, $downloadName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function storeFromTemplate(StoreTemplateEnvelopeRequest $request): JsonResponse
    {
        try {
            $response = $this->client->sendTemplateEnvelope($request->validated());
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Unable to call Signable API.',
                'error' => $e->getMessage(),
            ], 500);
        }

        if ($response->failed()) {
            return response()->json([
                'message' => 'Signable rejected the envelope request.',
                'status' => $response->status(),
                'error' => $response->json() ?? ['raw' => $response->body()],
            ], $response->status());
        }

        return response()->json([
            'message' => 'Envelope created successfully.',
            'data' => $response->json() ?? ['raw' => $response->body()],
        ], 201);
    }

    private function proxy(Response $response, int $successStatus = 200): JsonResponse
    {
        if ($response->failed()) {
            return response()->json([
                'message' => 'Signable API returned an error.',
                'status' => $response->status(),
                'error' => $response->json() ?? ['raw' => $response->body()],
            ], $response->status());
        }

        return response()->json(
            $response->json() ?? ['raw' => $response->body()],
            $successStatus
        );
    }

    private function serviceError(string $message): JsonResponse
    {
        return response()->json([
            'message' => 'Unable to call Signable API.',
            'error' => $message,
        ], 500);
    }

    private function findDownloadUrl(mixed $node): ?string
    {
        if (is_string($node)) {
            return preg_match('/^https?:\/\//i', $node) === 1 ? $node : null;
        }

        if (is_array($node)) {
            $preferredKeys = [
                'envelope_download_url',
                'download_url',
                'envelope_download',
                'signed_pdf_url',
                'pdf_url',
                'url',
            ];

            foreach ($preferredKeys as $key) {
                if (isset($node[$key]) && is_string($node[$key]) && preg_match('/^https?:\/\//i', $node[$key]) === 1) {
                    return $node[$key];
                }
            }

            foreach ($node as $key => $value) {
                if (is_string($key) && preg_match('/download|pdf/i', $key) === 1 && is_string($value) && preg_match('/^https?:\/\//i', $value) === 1) {
                    return $value;
                }
            }

            foreach ($node as $value) {
                $found = $this->findDownloadUrl($value);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function buildZipEntryName(array $envelopePayload, string $fingerprint, int $index, HttpResponse $fileResponse): string
    {
        $baseName = (string) ($envelopePayload['envelope_title'] ?? $envelopePayload['title'] ?? "envelope_{$fingerprint}");
        $safeBaseName = Str::limit(Str::slug($baseName, '_'), 60, '');
        if ($safeBaseName === '') {
            $safeBaseName = "envelope_{$index}";
        }

        $extension = $this->inferFileExtension($fileResponse);

        return "{$index}_{$safeBaseName}.{$extension}";
    }

    private function inferFileExtension(HttpResponse $fileResponse): string
    {
        $contentType = strtolower((string) $fileResponse->header('Content-Type'));
        if (str_contains($contentType, 'pdf')) {
            return 'pdf';
        }

        if (str_contains($contentType, 'json')) {
            return 'json';
        }

        if (str_contains($contentType, 'zip')) {
            return 'zip';
        }

        return 'bin';
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     * @param  array<string, mixed>  $responsePayload
     */
    private function persistDealEnvelope(int $dealId, array $requestPayload, array $responsePayload): void
    {
        $envelope = $this->extractEnvelopePayload($responsePayload);
        $fingerprint = $this->firstString($envelope, ['envelope_fingerprint', 'fingerprint', 'id']);

        if ($fingerprint === null || $fingerprint === '') {
            return;
        }

        $title = $this->firstString($envelope, ['envelope_title', 'title', 'name'])
            ?? (string) ($requestPayload['envelope_title'] ?? 'Untitled Envelope');

        $createdAt = $this->parseDateTime(
            $this->firstString($envelope, ['envelope_created', 'date_created', 'created_at', 'created'])
        );

        $signedAt = $this->parseDateTime(
            $this->firstString($envelope, ['envelope_processed', 'date_signed', 'signed_at', 'envelope_signed'])
        );

        $status = $this->firstString($envelope, ['envelope_status', 'status', 'action']);

        DealSignableEnvelope::query()->updateOrCreate(
            [
                'deal_id' => $dealId,
                'envelope_fingerprint' => $fingerprint,
            ],
            [
                'user_id' => (int) ($this->firstString($envelope, ['user_id', 'envelope_user_id']) ?? (string) ($requestPayload['user_id'] ?? 0)),
                'envelope_title' => $title,
                'date_created' => $createdAt,
                'date_signed' => $signedAt,
                'envelope_status' => $status,
                'download_link' => $this->findDownloadUrl($envelope),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractEnvelopePayload(array $payload): array
    {
        $candidates = [
            $payload,
            is_array($payload['data'] ?? null) ? $payload['data'] : null,
            is_array($payload['envelope'] ?? null) ? $payload['envelope'] : null,
            is_array($payload['data']['envelope'] ?? null) ? $payload['data']['envelope'] : null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            if ($this->firstString($candidate, ['envelope_fingerprint', 'fingerprint', 'id']) !== null) {
                return $candidate;
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function firstString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! isset($payload[$key])) {
                continue;
            }

            $value = trim((string) $payload[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
