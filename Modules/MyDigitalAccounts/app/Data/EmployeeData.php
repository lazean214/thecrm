<?php

declare(strict_types=1);

namespace Modules\MyDigitalAccounts\Data;

use DateTimeImmutable;

/**
 * Employee Data Transfer Object
 *
 * Represents an employee/contractor resource from the MyDigitalAccounts API
 */
readonly class EmployeeData
{
    public function __construct(
        public string $id,
        public string $companyId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone = null,
        public ?string $role = null,
        public ?string $department = null,
        public ?string $employeeType = null,
        public ?string $employeeId = null,
        public ?string $jobTitle = null,
        public ?DateTimeImmutable $hireDate = null,
        public ?DateTimeImmutable $terminationDate = null,
        public bool $isActive = true,
        public ?float $salary = null,
        public ?string $salaryFrequency = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * Create an EmployeeData instance from an API response array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            companyId: $data['company_id'] ?? '',
            firstName: $data['first_name'] ?? '',
            lastName: $data['last_name'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? null,
            role: $data['role'] ?? null,
            department: $data['department'] ?? null,
            employeeType: $data['employee_type'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            jobTitle: $data['job_title'] ?? null,
            hireDate: isset($data['hire_date']) ? new DateTimeImmutable($data['hire_date']) : null,
            terminationDate: isset($data['termination_date']) ? new DateTimeImmutable($data['termination_date']) : null,
            isActive: $data['is_active'] ?? true,
            salary: isset($data['salary']) ? (float) $data['salary'] : null,
            salaryFrequency: $data['salary_frequency'] ?? null,
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
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'department' => $this->department,
            'employee_type' => $this->employeeType,
            'employee_id' => $this->employeeId,
            'job_title' => $this->jobTitle,
            'is_active' => $this->isActive,
            'salary' => $this->salary,
            'salary_frequency' => $this->salaryFrequency,
        ];
    }
}
