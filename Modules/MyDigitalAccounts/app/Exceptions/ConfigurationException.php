<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all MyDigitalAccounts API errors
 */
class MyDigitalAccountsException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Exception thrown when API returns an error response
 */
class MyDigitalAccountsApiException extends MyDigitalAccountsException
{
    protected int $statusCode;

    protected array $apiResponse;

    public function __construct(
        string $message,
        int $statusCode,
        array $apiResponse = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->statusCode = $statusCode;
        $this->apiResponse = $apiResponse;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getApiResponse(): array
    {
        return $this->apiResponse;
    }
}

/**
 * Exception thrown when rate limit is exceeded
 */
class RateLimitException extends MyDigitalAccountsApiException
{
    protected int $retryAfter;

    public function __construct(
        string $message,
        int $retryAfter = 60,
        array $apiResponse = [],
        ?Throwable $previous = null,
    ) {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, 429, $apiResponse, 0, $previous);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

/**
 * Exception thrown when authentication fails
 */
class AuthenticationException extends MyDigitalAccountsApiException
{
    public function __construct(
        string $message = 'Authentication failed',
        array $apiResponse = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 401, $apiResponse, 0, $previous);
    }
}

/**
 * Exception thrown when authorization fails (forbidden)
 */
class AuthorizationException extends MyDigitalAccountsApiException
{
    public function __construct(
        string $message = 'Authorization failed',
        array $apiResponse = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 403, $apiResponse, 0, $previous);
    }
}

/**
 * Exception thrown when a resource is not found
 */
class ResourceNotFoundException extends MyDigitalAccountsApiException
{
    public function __construct(
        string $message = 'Resource not found',
        array $apiResponse = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 404, $apiResponse, 0, $previous);
    }
}

/**
 * Exception thrown when validation fails
 */
class ValidationException extends MyDigitalAccountsApiException
{
    protected array $errors;

    public function __construct(
        string $message,
        array $errors = [],
        array $apiResponse = [],
        ?Throwable $previous = null,
    ) {
        $this->errors = $errors;
        parent::__construct($message, 422, $apiResponse, 0, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Exception thrown for network/connection errors
 */
class ConnectionException extends MyDigitalAccountsException
{
    public function __construct(
        string $message = 'Connection to MyDigitalAccounts API failed',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Exception thrown when configuration is invalid
 */
class ConfigurationException extends MyDigitalAccountsException
{
    public function __construct(
        string $message = 'Invalid MyDigitalAccounts configuration',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
