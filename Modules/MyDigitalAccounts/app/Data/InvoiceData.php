<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Data;

use DateTimeImmutable;

/**
 * Invoice Data Transfer Object
 *
 * Represents an invoice/billing resource from the MyDigitalAccounts API
 */
readonly class InvoiceData
{
    public function __construct(
        public string $id,
        public string $companyId,
        public string $invoiceNumber,
        public float $amount,
        public string $currency,
        public string $status,
        public ?string $description = null,
        public ?DateTimeImmutable $issuedAt = null,
        public ?DateTimeImmutable $dueAt = null,
        public ?DateTimeImmutable $paidAt = null,
        public ?string $paymentMethod = null,
        public ?array $lineItems = null,
        public ?float $taxAmount = null,
        public ?float $discountAmount = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * Create an InvoiceData instance from an API response array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            companyId: $data['company_id'] ?? '',
            invoiceNumber: $data['invoice_number'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            currency: $data['currency'] ?? 'USD',
            status: $data['status'] ?? 'draft',
            description: $data['description'] ?? null,
            issuedAt: isset($data['issued_at']) ? new DateTimeImmutable($data['issued_at']) : null,
            dueAt: isset($data['due_at']) ? new DateTimeImmutable($data['due_at']) : null,
            paidAt: isset($data['paid_at']) ? new DateTimeImmutable($data['paid_at']) : null,
            paymentMethod: $data['payment_method'] ?? null,
            lineItems: $data['line_items'] ?? null,
            taxAmount: isset($data['tax_amount']) ? (float) $data['tax_amount'] : null,
            discountAmount: isset($data['discount_amount']) ? (float) $data['discount_amount'] : null,
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
            'company_id' => $this->companyId,
            'invoice_number' => $this->invoiceNumber,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'description' => $this->description,
            'payment_method' => $this->paymentMethod,
            'line_items' => $this->lineItems,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
        ];
    }
}
