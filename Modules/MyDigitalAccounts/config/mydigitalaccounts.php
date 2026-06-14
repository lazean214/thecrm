<?php

declare(strict_types=1);

/**
 * MyDigitalAccounts API Configuration
 *
 * This configuration file stores all settings for MyDigitalAccounts API integration,
 * including authentication credentials, endpoints, and request timeouts.
 *
 * Environment variables should be defined in your .env file:
 * - MYDIGITALACCOUNTS_BASE_URL
 * - MYDIGITALACCOUNTS_CLIENT_ID
 * - MYDIGITALACCOUNTS_CLIENT_SECRET
 * - MYDIGITALACCOUNTS_API_KEY (if using API key auth)
 * - MYDIGITALACCOUNTS_REQUEST_TIMEOUT
 */

return [
    // API Base URL for MyDigitalAccounts
    'base_url' => env('MYDIGITALACCOUNTS_BASE_URL', 'https://api.mydigitalaccounts.com/v1'),

    // Authentication Configuration
    'auth' => [
        // Supported: 'oauth2', 'api_key', 'bearer_token'
        'type' => env('MYDIGITALACCOUNTS_AUTH_TYPE', 'oauth2'),

        // OAuth2 Configuration
        'oauth2' => [
            'client_id' => env('MYDIGITALACCOUNTS_CLIENT_ID'),
            'client_secret' => env('MYDIGITALACCOUNTS_CLIENT_SECRET'),
            'token_endpoint' => env('MYDIGITALACCOUNTS_TOKEN_ENDPOINT', 'https://auth.mydigitalaccounts.com/oauth/token'),
            'scope' => env('MYDIGITALACCOUNTS_OAUTH_SCOPE', 'api:read api:write'),
        ],

        // API Key Configuration
        'api_key' => [
            'key' => env('MYDIGITALACCOUNTS_API_KEY'),
            'header_name' => env('MYDIGITALACCOUNTS_API_KEY_HEADER', 'X-API-Key'),
        ],

        // Bearer Token Configuration
        'bearer_token' => [
            'token' => env('MYDIGITALACCOUNTS_BEARER_TOKEN'),
        ],
    ],

    // HTTP Client Configuration
    'http' => [
        'timeout' => (int) env('MYDIGITALACCOUNTS_REQUEST_TIMEOUT', 30),
        'connect_timeout' => (int) env('MYDIGITALACCOUNTS_CONNECT_TIMEOUT', 10),
        'verify_ssl' => (bool) env('MYDIGITALACCOUNTS_VERIFY_SSL', true),
    ],

    // Rate Limiting Configuration
    'rate_limit' => [
        'enabled' => (bool) env('MYDIGITALACCOUNTS_RATE_LIMIT_ENABLED', true),
        'max_requests' => (int) env('MYDIGITALACCOUNTS_RATE_LIMIT_MAX_REQUESTS', 1000),
        'window_seconds' => (int) env('MYDIGITALACCOUNTS_RATE_LIMIT_WINDOW', 3600),
    ],

    // Cache Configuration for Tokens
    'cache' => [
        'enabled' => (bool) env('MYDIGITALACCOUNTS_CACHE_ENABLED', true),
        'driver' => env('MYDIGITALACCOUNTS_CACHE_DRIVER', 'file'),
        'ttl' => (int) env('MYDIGITALACCOUNTS_CACHE_TTL', 3600),
    ],

    // Retry Configuration
    'retry' => [
        'enabled' => (bool) env('MYDIGITALACCOUNTS_RETRY_ENABLED', true),
        'max_attempts' => (int) env('MYDIGITALACCOUNTS_MAX_RETRIES', 3),
        'delay_ms' => (int) env('MYDIGITALACCOUNTS_RETRY_DELAY', 1000),
    ],
];
