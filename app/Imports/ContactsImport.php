<?php

namespace App\Imports;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    /**
     * @return Model|null
     */
    public function model(array $row)
    {
        return new Contact([
            'first_name' => $row['first_name'] ?? $row['firstname'] ?? null,
            'last_name' => $row['last_name'] ?? $row['lastname'] ?? null,
            'email' => $row['email'] ?? $row['email_address'] ?? null,
            'phone' => $row['phone'] ?? $row['phone_number'] ?? null,
            'street_address' => $row['street_address'] ?? $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? $row['province'] ?? null,
            'postal_code' => $row['postal_code'] ?? $row['postcode'] ?? $row['zip'] ?? null,
            'country' => $row['country'] ?? null,
            'ni_number' => $row['ni_number'] ?? $row['national_insurance'] ?? null,
            'bank' => $row['bank'] ?? $row['bank_name'] ?? null,
            'account_number' => $row['account_number'] ?? null,
            'sort_code' => $row['sort_code'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? $row['dob'] ?? null,
            'marital_status' => $row['marital_status'] ?? null,
            'gender' => $row['gender'] ?? $row['sex'] ?? null,
        ]);
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
