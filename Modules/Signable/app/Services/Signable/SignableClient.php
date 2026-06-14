<?php

namespace Modules\Signable\App\Services\Signable;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SignableClient
{
    // -------------------------------------------------------------------------
    // Core
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data  Query params for GET, body for POST/PUT/PATCH.
     */
    private function request(string $method, string $path, array $data = []): Response
    {
        $server = rtrim((string) config('modules.signable.api.server', config('services.signable.server', 'https://api.signable.co.uk/v1')), '/');
        $key = (string) config('modules.signable.api.key', config('services.signable.key'));
        $secret = (string) config('modules.signable.api.secret', config('services.signable.secret', 'x'));
        $timeout = (int) config('modules.signable.api.timeout', config('services.signable.timeout', 30));

        if ($key === '') {
            throw new RuntimeException('SIGNABLE_API_KEY is not configured.');
        }

        $http = Http::acceptJson()
            ->asJson()
            ->withBasicAuth($key, $secret)
            ->timeout($timeout);

        return match (strtoupper($method)) {
            'GET' => $http->get($server.$path, $data),
            'POST' => $http->post($server.$path, $data),
            'PUT' => $http->put($server.$path, $data),
            'PATCH' => $http->patch($server.$path, $data),
            'DELETE' => $http->delete($server.$path),
            default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
        };
    }

    // -------------------------------------------------------------------------
    // Envelopes
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $params */
    public function listEnvelopes(array $params = []): Response
    {
        return $this->request('GET', '/envelopes', $params);
    }

    /** @param array<string, mixed> $payload */
    public function sendEnvelope(array $payload): Response
    {
        return $this->request('POST', '/envelopes', $this->normalizeEnvelopePayload($payload));
    }

    /** @param array<string, mixed> $payload — BC alias used by existing controller */
    public function sendTemplateEnvelope(array $payload): Response
    {
        return $this->sendEnvelope($payload);
    }

    public function getEnvelope(string $fingerprint): Response
    {
        return $this->request('GET', '/envelopes/'.$fingerprint);
    }

    public function deleteEnvelope(string $fingerprint): Response
    {
        return $this->request('DELETE', '/envelopes/'.$fingerprint);
    }

    public function remindEnvelope(string $fingerprint): Response
    {
        return $this->request('PUT', '/envelopes/'.$fingerprint.'/remind');
    }

    public function cancelEnvelope(string $fingerprint): Response
    {
        return $this->request('PUT', '/envelopes/'.$fingerprint.'/cancel');
    }

    public function expireEnvelope(string $fingerprint): Response
    {
        return $this->request('PUT', '/envelopes/'.$fingerprint.'/expire');
    }

    /** @param array<string, mixed> $payload */
    public function updateEnvelopeParty(string $fingerprint, string $partyId, array $payload): Response
    {
        return $this->request('PATCH', '/envelopes/'.$fingerprint.'/parties/'.$partyId, $payload);
    }

    // -------------------------------------------------------------------------
    // Templates
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $params */
    public function listTemplates(array $params = []): Response
    {
        return $this->request('GET', '/templates', $params);
    }

    public function getTemplate(string $fingerprint): Response
    {
        return $this->request('GET', '/templates/'.$fingerprint);
    }

    public function deleteTemplate(string $fingerprint): Response
    {
        return $this->request('DELETE', '/templates/'.$fingerprint);
    }

    // -------------------------------------------------------------------------
    // Contacts
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $params */
    public function listContacts(array $params = []): Response
    {
        return $this->request('GET', '/contacts', $params);
    }

    /** @param array<string, mixed> $payload */
    public function createContact(array $payload): Response
    {
        return $this->request('POST', '/contacts', $payload);
    }

    public function getContact(int $contactId): Response
    {
        return $this->request('GET', '/contacts/'.$contactId);
    }

    /** @param array<string, mixed> $payload */
    public function updateContact(int $contactId, array $payload): Response
    {
        return $this->request('PUT', '/contacts/'.$contactId, $payload);
    }

    public function deleteContact(int $contactId): Response
    {
        return $this->request('DELETE', '/contacts/'.$contactId);
    }

    /** @param array<string, mixed> $params */
    public function listContactEnvelopes(int $contactId, array $params = []): Response
    {
        return $this->request('GET', '/contacts/'.$contactId.'/envelopes', $params);
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $params */
    public function listUsers(array $params = []): Response
    {
        return $this->request('GET', '/users', $params);
    }

    /** @param array<string, mixed> $payload */
    public function createUser(array $payload): Response
    {
        return $this->request('POST', '/users', $payload);
    }

    public function getUser(int $userId): Response
    {
        return $this->request('GET', '/users/'.$userId);
    }

    /** @param array<string, mixed> $payload */
    public function updateUser(int $userId, array $payload): Response
    {
        return $this->request('PUT', '/users/'.$userId, $payload);
    }

    public function deleteUser(int $userId): Response
    {
        return $this->request('DELETE', '/users/'.$userId);
    }

    // -------------------------------------------------------------------------
    // Webhooks
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $params */
    public function listWebhooks(array $params = []): Response
    {
        return $this->request('GET', '/webhooks', $params);
    }

    /** @param array<string, mixed> $payload */
    public function createWebhook(array $payload): Response
    {
        return $this->request('POST', '/webhooks', $payload);
    }

    public function getWebhook(int $webhookId): Response
    {
        return $this->request('GET', '/webhooks/'.$webhookId);
    }

    /** @param array<string, mixed> $payload */
    public function updateWebhook(int $webhookId, array $payload): Response
    {
        return $this->request('PUT', '/webhooks/'.$webhookId, $payload);
    }

    public function deleteWebhook(int $webhookId): Response
    {
        return $this->request('DELETE', '/webhooks/'.$webhookId);
    }

    // -------------------------------------------------------------------------
    // Branding
    // -------------------------------------------------------------------------

    public function getBranding(): Response
    {
        return $this->request('GET', '/branding');
    }

    /** @param array<string, mixed> $payload */
    public function updateBranding(array $payload): Response
    {
        return $this->request('PUT', '/branding', $payload);
    }

    public function getBrandingEmails(): Response
    {
        return $this->request('GET', '/branding/emails');
    }

    /** @param array<string, mixed> $payload */
    public function updateBrandingEmail(string $type, array $payload): Response
    {
        return $this->request('PUT', '/branding/emails/'.$type, $payload);
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function getSettings(): Response
    {
        return $this->request('GET', '/settings');
    }

    /** @param array<string, mixed> $payload */
    public function updateSettings(array $payload): Response
    {
        return $this->request('PUT', '/settings', $payload);
    }

    /**
     * Signable expects all send-envelope variants to provide envelope_documents.
     * Keep backward compatibility with the local API by accepting legacy keys.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeEnvelopePayload(array $payload): array
    {
        if (isset($payload['envelope_documents']) && is_array($payload['envelope_documents'])) {
            $payload['envelope_documents'] = array_map(
                function (mixed $document) use ($payload): mixed {
                    if (! is_array($document)) {
                        return $document;
                    }

                    // Backward compatibility for older payloads.
                    if (! isset($document['document_template_fingerprint']) && isset($document['template_fingerprint'])) {
                        $document['document_template_fingerprint'] = $document['template_fingerprint'];
                    }

                    if (isset($document['document_template_fingerprint']) && ! isset($document['document_title'])) {
                        $document['document_title'] = (string) ($payload['envelope_title'] ?? 'Template document');
                    }

                    unset($document['template_fingerprint']);

                    return $document;
                },
                $payload['envelope_documents']
            );
        }

        if (! isset($payload['envelope_documents']) || ! is_array($payload['envelope_documents']) || $payload['envelope_documents'] === []) {
            if (isset($payload['template_id']) && is_string($payload['template_id']) && $payload['template_id'] !== '') {
                $payload['envelope_documents'] = [
                    [
                        'document_title' => (string) ($payload['template_title'] ?? $payload['envelope_title'] ?? 'Template document'),
                        'document_template_fingerprint' => $payload['template_id'],
                    ],
                ];
            } elseif (isset($payload['envelope_document']) && is_array($payload['envelope_document'])) {
                $payload['envelope_documents'] = [$payload['envelope_document']];
            }
        }

        $templateFingerprints = collect($payload['envelope_documents'] ?? [])
            ->pluck('document_template_fingerprint')
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        if ($templateFingerprints !== [] && isset($payload['envelope_parties']) && is_array($payload['envelope_parties'])) {
            $payload['envelope_parties'] = array_map(
                function (mixed $party) use ($templateFingerprints): mixed {
                    if (! is_array($party)) {
                        return $party;
                    }

                    // Signable rejects party payloads containing both top-level party_id and party_documents.
                    if (isset($party['party_documents']) && is_array($party['party_documents'])) {
                        unset($party['party_id']);
                    }

                    if (! isset($party['party_documents']) && isset($party['party_id']) && is_string($party['party_id']) && count($templateFingerprints) === 1) {
                        $party['party_documents'] = [[
                            'party_id' => $party['party_id'],
                            'document_template_fingerprint' => $templateFingerprints[0],
                        ]];
                    }

                    unset($party['party_id']);

                    return $party;
                },
                $payload['envelope_parties']
            );
        }

        unset($payload['template_id'], $payload['template_title'], $payload['envelope_document']);

        return $payload;
    }
}
