<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Domain;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\MyDigitalAccounts\Exceptions\AuthenticationException;
use Modules\MyDigitalAccounts\Exceptions\AuthorizationException;
use Modules\MyDigitalAccounts\Exceptions\ConfigurationException;
use Modules\MyDigitalAccounts\Exceptions\ConnectionException;
use Modules\MyDigitalAccounts\Exceptions\MyDigitalAccountsApiException;
use Modules\MyDigitalAccounts\Exceptions\RateLimitException;
use Modules\MyDigitalAccounts\Exceptions\ResourceNotFoundException;
use Modules\MyDigitalAccounts\Exceptions\ValidationException;

/**
 * MyDigitalAccounts API Client
 *
 * Central HTTP client wrapper handling:
 * - Authentication (OAuth2, API Key, Bearer Token)
 * - Token caching and refresh
 * - Rate limiting
 * - Exception mapping
 * - Request/Response handling
 */
class MyDigitalAccountsClient
{
    private const TOKEN_CACHE_KEY = 'mydigitalaccounts:oauth:token';

    private const RATE_LIMIT_CACHE_KEY = 'mydigitalaccounts:rate_limit';

    private string $baseUrl;

    private string $authType;

    private array $config;

    private ?string $cachedToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? throw new ConfigurationException(
            'MyDigitalAccounts base_url is not configured'
        );
        $this->authType = $config['auth']['type'] ?? 'oauth2';

        $this->validateConfiguration();
    }

    /**
     * Validate that all required configuration is present
     */
    private function validateConfiguration(): void
    {
        match ($this->authType) {
            'oauth2' => $this->validateOAuth2Config(),
            'api_key' => $this->validateApiKeyConfig(),
            'bearer_token' => $this->validateBearerTokenConfig(),
            default => throw new ConfigurationException("Unsupported auth type: {$this->authType}"),
        };
    }

    private function validateOAuth2Config(): void
    {
        $oauth2 = $this->config['auth']['oauth2'] ?? [];
        if (empty($oauth2['client_id']) || empty($oauth2['client_secret'])) {
            throw new ConfigurationException(
                'OAuth2 authentication requires client_id and client_secret'
            );
        }
    }

    private function validateApiKeyConfig(): void
    {
        $apiKey = $this->config['auth']['api_key'] ?? [];
        if (empty($apiKey['key'])) {
            throw new ConfigurationException(
                'API Key authentication requires an API key to be configured'
            );
        }
    }

    private function validateBearerTokenConfig(): void
    {
        $token = $this->config['auth']['bearer_token'] ?? [];
        if (empty($token['token'])) {
            throw new ConfigurationException(
                'Bearer token authentication requires a token to be configured'
            );
        }
    }

    /**
     * Make a GET request to the API
     *
     * @param  string  $endpoint  API endpoint path (e.g., '/companies')
     * @param  array<string, mixed>  $params  Query parameters
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, [], $params);
    }

    /**
     * Make a POST request to the API
     *
     * @param  string  $endpoint  API endpoint path
     * @param  array<string, mixed>  $data  Request body data
     * @param  array<string, mixed>  $params  Query parameters
     */
    public function post(string $endpoint, array $data = [], array $params = []): array
    {
        return $this->request('POST', $endpoint, $data, $params);
    }

    /**
     * Make a PUT request to the API
     *
     * @param  string  $endpoint  API endpoint path
     * @param  array<string, mixed>  $data  Request body data
     * @param  array<string, mixed>  $params  Query parameters
     */
    public function put(string $endpoint, array $data = [], array $params = []): array
    {
        return $this->request('PUT', $endpoint, $data, $params);
    }

    /**
     * Make a PATCH request to the API
     *
     * @param  string  $endpoint  API endpoint path
     * @param  array<string, mixed>  $data  Request body data
     * @param  array<string, mixed>  $params  Query parameters
     */
    public function patch(string $endpoint, array $data = [], array $params = []): array
    {
        return $this->request('PATCH', $endpoint, $data, $params);
    }

    /**
     * Make a DELETE request to the API
     *
     * @param  string  $endpoint  API endpoint path
     * @param  array<string, mixed>  $params  Query parameters
     */
    public function delete(string $endpoint, array $params = []): array
    {
        return $this->request('DELETE', $endpoint, [], $params);
    }

    /**
     * Execute an HTTP request with retries, rate limiting, and error handling
     *
     * @param  string  $method  HTTP method
     * @param  string  $endpoint  API endpoint path
     * @param  array<string, mixed>  $data  Request body
     * @param  array<string, mixed>  $params  Query parameters
     */
    private function request(
        string $method,
        string $endpoint,
        array $data = [],
        array $params = [],
    ): array {
        $this->checkRateLimit();
        $maxAttempts = $this->config['retry']['max_attempts'] ?? 3;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $response = $this->executeRequest($method, $endpoint, $data, $params);
                $this->recordRateLimit($response);

                return $response->json(associative: true) ?? [];
            } catch (ConnectionException $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                $delay = ($this->config['retry']['delay_ms'] ?? 1000) * $attempt;
                usleep($delay * 1000);
            }
        }

        throw new ConnectionException('Failed to connect to MyDigitalAccounts API after retries');
    }

    /**
     * Execute the actual HTTP request
     */
    private function executeRequest(
        string $method,
        string $endpoint,
        array $data,
        array $params,
    ): Response {
        try {
            $pendingRequest = $this->getPendingRequest();

            if (! empty($params)) {
                $pendingRequest = $pendingRequest->withQueryParameters($params);
            }

            $url = $this->buildUrl($endpoint);

            $response = match ($method) {
                'GET' => $pendingRequest->get($url),
                'POST' => $pendingRequest->post($url, $data),
                'PUT' => $pendingRequest->put($url, $data),
                'PATCH' => $pendingRequest->patch($url, $data),
                'DELETE' => $pendingRequest->delete($url),
                default => throw new ConfigurationException("Unsupported HTTP method: {$method}"),
            };

            $this->handleResponse($response);

            return $response;
        } catch (ConnectionException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ConnectionException(
                'Connection to MyDigitalAccounts API failed: '.$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Get a configured pending HTTP request
     */
    private function getPendingRequest(): PendingRequest
    {
        $config = $this->config['http'] ?? [];
        $timeout = (int) ($config['timeout'] ?? 30);
        $connectTimeout = (int) ($config['connect_timeout'] ?? 10);
        $verifySsl = (bool) ($config['verify_ssl'] ?? true);

        $pendingRequest = Http::timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->withoutVerifying() // Conditionally set based on verifySsl
            ->acceptJson()
            ->contentType('application/json');

        if ($verifySsl) {
            $pendingRequest = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->acceptJson()
                ->contentType('application/json');
        }

        // Add authentication headers
        $pendingRequest = $this->addAuthenticationHeaders($pendingRequest);

        return $pendingRequest;
    }

    /**
     * Add authentication headers to the pending request
     */
    private function addAuthenticationHeaders(PendingRequest $pendingRequest): PendingRequest
    {
        return match ($this->authType) {
            'oauth2' => $this->addOAuth2Headers($pendingRequest),
            'api_key' => $this->addApiKeyHeaders($pendingRequest),
            'bearer_token' => $this->addBearerTokenHeaders($pendingRequest),
            default => $pendingRequest,
        };
    }

    /**
     * Add OAuth2 Bearer token header
     */
    private function addOAuth2Headers(PendingRequest $pendingRequest): PendingRequest
    {
        $token = $this->getOrRefreshToken();

        return $pendingRequest->withToken($token);
    }

    /**
     * Get or refresh the OAuth2 access token
     */
    private function getOrRefreshToken(): string
    {
        if ($this->cachedToken !== null) {
            return $this->cachedToken;
        }

        // Check cache
        if ($this->config['cache']['enabled'] ?? true) {
            $cached = Cache::store($this->config['cache']['driver'] ?? 'file')
                ->get(self::TOKEN_CACHE_KEY);

            if ($cached !== null) {
                $this->cachedToken = $cached;

                return $cached;
            }
        }

        // Refresh token
        $token = $this->refreshOAuth2Token();
        $this->cachedToken = $token;

        // Cache token
        if ($this->config['cache']['enabled'] ?? true) {
            $ttl = (int) ($this->config['cache']['ttl'] ?? 3600);
            Cache::store($this->config['cache']['driver'] ?? 'file')
                ->put(self::TOKEN_CACHE_KEY, $token, $ttl - 60);
        }

        return $token;
    }

    /**
     * Refresh the OAuth2 access token from the token endpoint
     */
    private function refreshOAuth2Token(): string
    {
        $oauth2 = $this->config['auth']['oauth2'] ?? [];
        $tokenEndpoint = $oauth2['token_endpoint'] ?? 'https://auth.mydigitalaccounts.com/oauth/token';

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post($tokenEndpoint, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $oauth2['client_id'],
                    'client_secret' => $oauth2['client_secret'],
                    'scope' => $oauth2['scope'] ?? 'api:read api:write',
                ]);

            if (! $response->successful()) {
                throw new AuthenticationException(
                    'Failed to obtain OAuth2 access token',
                    $response->json(),
                );
            }

            $data = $response->json();

            return $data['access_token'] ?? throw new AuthenticationException(
                'No access token in OAuth2 response'
            );
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException(
                'OAuth2 token refresh failed: '.$e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Add API Key header
     */
    private function addApiKeyHeaders(PendingRequest $pendingRequest): PendingRequest
    {
        $apiKey = $this->config['auth']['api_key'] ?? [];
        $headerName = $apiKey['header_name'] ?? 'X-API-Key';
        $key = $apiKey['key'] ?? '';

        return $pendingRequest->withHeaders([
            $headerName => $key,
        ]);
    }

    /**
     * Add Bearer token header
     */
    private function addBearerTokenHeaders(PendingRequest $pendingRequest): PendingRequest
    {
        $token = $this->config['auth']['bearer_token']['token'] ?? '';

        return $pendingRequest->withToken($token);
    }

    /**
     * Build the full URL for an endpoint
     */
    private function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');

        return rtrim($this->baseUrl, '/').'/'.$endpoint;
    }

    /**
     * Handle API response and throw exceptions for errors
     */
    private function handleResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $statusCode = $response->status();
        $body = $response->json(associative: true) ?? [];

        match ($statusCode) {
            401 => throw new AuthenticationException(
                $body['message'] ?? 'Unauthorized',
                $body,
            ),
            403 => throw new AuthorizationException(
                $body['message'] ?? 'Forbidden',
                $body,
            ),
            404 => throw new ResourceNotFoundException(
                $body['message'] ?? 'Resource not found',
                $body,
            ),
            422 => throw new ValidationException(
                $body['message'] ?? 'Validation failed',
                $body['errors'] ?? [],
                $body,
            ),
            429 => throw new RateLimitException(
                $body['message'] ?? 'Rate limit exceeded',
                (int) ($response->header('Retry-After') ?? 60),
                $body,
            ),
            default => throw new MyDigitalAccountsApiException(
                $body['message'] ?? "API error: {$statusCode}",
                $statusCode,
                $body,
            ),
        };
    }

    /**
     * Check if rate limit has been exceeded
     */
    private function checkRateLimit(): void
    {
        if (! ($this->config['rate_limit']['enabled'] ?? true)) {
            return;
        }

        $cached = Cache::get(self::RATE_LIMIT_CACHE_KEY);
        if ($cached === null) {
            return;
        }

        ['count' => $count, 'reset_at' => $resetAt] = $cached;

        if ($count >= ($this->config['rate_limit']['max_requests'] ?? 1000)) {
            $retryAfter = max(0, $resetAt - time());
            throw new RateLimitException(
                'Rate limit exceeded',
                max(1, $retryAfter),
            );
        }
    }

    /**
     * Record rate limit information from response headers
     */
    private function recordRateLimit(Response $response): void
    {
        if (! ($this->config['rate_limit']['enabled'] ?? true)) {
            return;
        }

        $remaining = $response->header('X-RateLimit-Remaining');
        $limit = $response->header('X-RateLimit-Limit');
        $reset = $response->header('X-RateLimit-Reset');

        if ($remaining === null || $limit === null) {
            return;
        }

        $count = (int) $limit - (int) $remaining;
        $resetAt = $reset ? (int) $reset : time() + 3600;

        Cache::put(
            self::RATE_LIMIT_CACHE_KEY,
            ['count' => $count, 'reset_at' => $resetAt],
            60,
        );
    }

    /**
     * Clear cached authentication tokens
     */
    public function clearCache(): void
    {
        $this->cachedToken = null;
        Cache::forget(self::TOKEN_CACHE_KEY);
    }
}
