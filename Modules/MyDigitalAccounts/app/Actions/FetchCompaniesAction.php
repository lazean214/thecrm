<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Actions;

use Modules\MyDigitalAccounts\Data\CompanyData;
use Modules\MyDigitalAccounts\Data\EmployeeData;
use Modules\MyDigitalAccounts\Data\InvoiceData;
use Modules\MyDigitalAccounts\Data\PaginatedData;
use Modules\MyDigitalAccounts\Domain\MyDigitalAccountsClient;

/**
 * Fetch all companies from the API
 *
 * Single-responsibility action that retrieves a paginated list of companies
 */
class FetchCompaniesAction
{
    public function __construct(
        private MyDigitalAccountsClient $client,
    ) {}

    /**
     * Execute the action
     *
     * @param  int  $page  Page number (1-based)
     * @param  int  $perPage  Results per page
     * @param  array<string, mixed>  $filters  Additional filter parameters
     */
    public function execute(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
    ): PaginatedData {
        $params = array_merge($filters, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/companies', $params);

        return PaginatedData::fromArray($response);
    }

    /**
     * Fetch a single company by ID
     */
    public function fetchById(string $companyId): CompanyData
    {
        $response = $this->client->get("/companies/{$companyId}");

        return CompanyData::fromArray($response);
    }
}

/**
 * Create a new company in the API
 *
 * Single-responsibility action that validates and creates a company resource
 */
class CreateCompanyAction
{
    public function __construct(
        private MyDigitalAccountsClient $client,
    ) {}

    /**
     * Execute the action
     *
     * @param  array<string, mixed>  $data  Company data
     */
    public function execute(array $data): CompanyData
    {
        // Validate required fields
        $this->validateData($data);

        // Normalize and prepare payload
        $payload = $this->preparePayload($data);

        // Call API
        $response = $this->client->post('/companies', $payload);

        // Return typed DTO
        return CompanyData::fromArray($response);
    }

    /**
     * Validate that required fields are present
     *
     * @throws \InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        $required = ['name', 'email'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Required field missing: {$field}");
            }
        }

        // Validate email format
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$data['email']}");
        }
    }

    /**
     * Prepare and normalize the payload
     */
    private function preparePayload(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? null,
            'state' => $data['state'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'address' => $data['address'] ?? null,
        ];
    }
}

/**
 * Update an existing company
 *
 * Single-responsibility action that updates a company resource
 */
class UpdateCompanyAction
{
    public function __construct(
        private MyDigitalAccountsClient $client,
    ) {}

    /**
     * Execute the action
     *
     * @param  string  $companyId  Company ID to update
     * @param  array<string, mixed>  $data  Updated company data
     */
    public function execute(string $companyId, array $data): CompanyData
    {
        // Prepare payload (only non-null values)
        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => isset($data['email']) ? strtolower(trim($data['email'])) : null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'industry' => $data['industry'] ?? null,
            'country' => $data['country'] ?? null,
            'state' => $data['state'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($value) => $value !== null);

        $response = $this->client->patch("/companies/{$companyId}", $payload);

        return CompanyData::fromArray($response);
    }
}

/**
 * Fetch all invoices from the API
 *
 * Single-responsibility action that retrieves a paginated list of invoices
 */
class FetchInvoicesAction
{
    public function __construct(
        private MyDigitalAccountsClient $client,
    ) {}

    /**
     * Execute the action
     *
     * @param  int  $page  Page number (1-based)
     * @param  int  $perPage  Results per page
     * @param  array<string, mixed>  $filters  Filter parameters (company_id, status, etc.)
     */
    public function execute(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
    ): PaginatedData {
        $params = array_merge($filters, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/invoices', $params);

        return PaginatedData::fromArray($response);
    }

    /**
     * Fetch a single invoice by ID
     */
    public function fetchById(string $invoiceId): InvoiceData
    {
        $response = $this->client->get("/invoices/{$invoiceId}");

        return InvoiceData::fromArray($response);
    }

    /**
     * Fetch invoices for a specific company
     */
    public function fetchByCompanyId(
        string $companyId,
        int $page = 1,
        int $perPage = 15,
    ): PaginatedData {
        return $this->execute($page, $perPage, ['company_id' => $companyId]);
    }

    /**
     * Fetch invoices by status
     */
    public function fetchByStatus(
        string $status,
        int $page = 1,
        int $perPage = 15,
    ): PaginatedData {
        return $this->execute($page, $perPage, ['status' => $status]);
    }
}

/**
 * Create a new invoice in the API
 *
 * Single-responsibility action that validates and creates an invoice resource
 */
class CreateInvoiceAction
{
    public function __construct(
        private MyDigitalAccountsClient $client,
    ) {}

    /**
     * Execute the action
     *
     * @param  array<string, mixed>  $data  Invoice data
     */
    public function execute(array $data): InvoiceData
    {
        // Validate required fields
        $this->validateData($data);

        // Prepare payload
        $payload = $this->preparePayload($data);

        // Call API
        $response = $this->client->post('/invoices', $payload);

        return InvoiceData::fromArray($response);
    }

    /**
     * Validate required invoice fields
     *
     * @throws \InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        $required = ['company_id', 'invoice_number', 'amount', 'currency'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Required field missing: {$field}");
            }
        }

        // Validate amount is positive
        if ((float) $data['amount'] <= 0) {
            throw new \InvalidArgumentException('Invoice amount must be greater than 0');
        }

        // Validate currency code (ISO 4217)
        if (strlen($data['currency']) !== 3) {
            throw new \InvalidArgumentException('Currency must be a valid ISO 4217 code');
        }
    }

    /**
     * Prepare the invoice payload
     */
    private function preparePayload(array $data): array
    {
        return [
            'company_id' => $data['company_id'],
            'invoice_number' => trim($data['invoice_number']),
            'amount' => (float) $data['amount'],
            'currency' => strtoupper($data['currency']),
            'status' => $data['status'] ?? 'draft',
            'description' => $data['description'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'line_items' => $data['line_items'] ?? null,
            'tax_amount' => isset($data['tax_amount']) ? (float) $data['tax_amount'] : null,
            'discount_amount' => isset($data['discount_amount']) ? (float) $data['discount_amount'] : null,
        ];
    }
}

/**
 * Fetch all employees from the API
 *
 * Single-responsibility action that retrieves a paginated list of employees
 */
class FetchEmployeesAction
{
    public function __construct(
        private MyDigitalAccountsClient $client,
    ) {}

    /**
     * Execute the action
     *
     * @param  int  $page  Page number (1-based)
     * @param  int  $perPage  Results per page
     * @param  array<string, mixed>  $filters  Filter parameters
     */
    public function execute(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
    ): PaginatedData {
        $params = array_merge($filters, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/employees', $params);

        return PaginatedData::fromArray($response);
    }

    /**
     * Fetch a single employee by ID
     */
    public function fetchById(string $employeeId): EmployeeData
    {
        $response = $this->client->get("/employees/{$employeeId}");

        return EmployeeData::fromArray($response);
    }

    /**
     * Fetch employees for a specific company
     */
    public function fetchByCompanyId(
        string $companyId,
        int $page = 1,
        int $perPage = 15,
    ): PaginatedData {
        return $this->execute($page, $perPage, ['company_id' => $companyId]);
    }

    /**
     * Fetch active employees
     */
    public function fetchActive(
        int $page = 1,
        int $perPage = 15,
    ): PaginatedData {
        return $this->execute($page, $perPage, ['is_active' => true]);
    }
}

/**
 * Create a new employee in the API
 *
 * Single-responsibility action that validates and creates an employee resource
 */
class CreateEmployeeAction
{
    public function __construct(
        private MyDigitalAccountsClient $client,
    ) {}

    /**
     * Execute the action
     *
     * @param  array<string, mixed>  $data  Employee data
     */
    public function execute(array $data): EmployeeData
    {
        // Validate required fields
        $this->validateData($data);

        // Prepare payload
        $payload = $this->preparePayload($data);

        // Call API
        $response = $this->client->post('/employees', $payload);

        return EmployeeData::fromArray($response);
    }

    /**
     * Validate required employee fields
     *
     * @throws \InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        $required = ['company_id', 'first_name', 'last_name', 'email'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Required field missing: {$field}");
            }
        }

        // Validate email format
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$data['email']}");
        }
    }

    /**
     * Prepare the employee payload
     */
    private function preparePayload(array $data): array
    {
        return [
            'company_id' => $data['company_id'],
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? null,
            'department' => $data['department'] ?? null,
            'employee_type' => $data['employee_type'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'hire_date' => $data['hire_date'] ?? null,
            'salary' => isset($data['salary']) ? (float) $data['salary'] : null,
            'salary_frequency' => $data['salary_frequency'] ?? null,
        ];
    }
}
