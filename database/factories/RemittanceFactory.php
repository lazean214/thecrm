<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Remittance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Remittance>
 */
class RemittanceFactory extends Factory
{
    protected $model = Remittance::class;

    public function definition(): array
    {
        $contact = Contact::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();
        $company = Company::inRandomOrder()->first();

        return [
            'week_no' => fake()->numberBetween(1, 52),
            'contact_id' => $contact?->id,
            'user_id' => $user?->id,
            'amount' => fake()->randomFloat(2, 100, 2000),
            'date_added' => fake()->date(),
            'status' => fake()->randomElement(['pending', 'approved', 'paid', 'rejected']),
            'deal_owner' => $user?->id,
            'company_id' => $company?->id,
            'margin_agreed' => fake()->randomFloat(2, 5, 50),
            'hours' => fake()->randomFloat(1, 10, 40),
            'rate' => fake()->randomFloat(2, 15, 30),
            'shirft_date' => fake()->optional()->date(),
            'we_date' => fake()->optional()->date(),
            'remarks' => fake()->optional()->sentence(),
            'compliance' => fake()->boolean(),
        ];
    }
}
