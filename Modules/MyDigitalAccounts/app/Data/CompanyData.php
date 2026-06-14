<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Data;

use DateTimeImmutable;

/**
 * Company Data Transfer Object
 *
 * Represents a company/client resource from the MyDigitalAccounts API
 */
readonly class CompanyData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $taxNumber = null,
        public ?string $registrationNumber = null,
        public ?string $industry = null,
        public ?string $country = null,
        public ?string $state = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $address = null,
        public bool $isActive = true,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * Create a CompanyData instance from an API response array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? null,
            website: $data['website'] ?? null,
            taxNumber: $data['tax_number'] ?? null,
            registrationNumber: $data['registration_number'] ?? null,
            industry: $data['industry'] ?? null,
            country: $data['country'] ?? null,
            state: $data['state'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            address: $data['address'] ?? null,
            isActive: $data['is_active'] ?? true,
            createdAt: isset($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new DateTimeImmutable($data['updated_at']) : null,
        );
    }

    /**
     * Convert the DTO to an array for requests
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'tax_number' => $this->taxNumber,
            'registration_number' => $this->registrationNumber,
            'industry' => $this->industry,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'address' => $this->address,
            'is_active' => $this->isActive,
        ];
    }
}
