<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Data;

/**
 * Paginated Response Data Transfer Object
 *
 * Represents a paginated collection response from the API
 */
readonly class PaginatedData
{
    /**
     * @param  array<int, array>  $data
     */
    public function __construct(
        public array $data,
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
        public bool $hasMore,
    ) {}

    /**
     * Create a PaginatedData instance from an API response array
     */
    public static function fromArray(array $response): self
    {
        return new self(
            data: $response['data'] ?? [],
            currentPage: (int) ($response['current_page'] ?? 1),
            perPage: (int) ($response['per_page'] ?? 15),
            total: (int) ($response['total'] ?? 0),
            lastPage: (int) ($response['last_page'] ?? 1),
            hasMore: (bool) ($response['has_more'] ?? false),
        );
    }
}
