<?php

namespace Database\Seeders;

use App\Models\InternalCompany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InternalCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = collect(config('internal_companies.companies', []))
            ->filter(fn ($name) => ! empty(trim($name)))
            ->map(fn ($name) => ['name' => trim($name), 'slug' => Str::slug($name)]);

        foreach ($companies as $company) {
            InternalCompany::firstOrCreate(
                ['slug' => $company['slug']],
                ['name' => $company['name']]
            );
        }
    }
}
