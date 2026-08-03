<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        $company = Company::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        return [
            'name' => fake()->name(),

            'amount' => fake()->numberBetween(
                5000,
                50000
            ),

            'stage' => fake()->randomElement(
                array_map(
                    fn ($stage) => $stage->value,
                    DealStage::cases()
                )
            ),

            'recruitment_agency' => fake()->randomElement([
                'Inbound',
                'Referral',
            ]),

            // from company name
            'consultant_name' => $company?->name,

            'agency_deal_value' => fake()->randomFloat(
                2,
                1000,
                25000
            ),

            'margin_agreed' => fake()->randomFloat(
                2,
                5,
                40
            ),

            'date_sent' => fake()
                ->optional()
                ->date(),

            'date_signed' => fake()
                ->optional()
                ->date(),

            'who_signed' => fake()
                ->optional()
                ->name(),

            'date_set_up' => fake()
                ->optional()
                ->date(),

            'remittance_received' => fake()
                ->boolean(),

            'date_logged' => fake()
                ->optional()
                ->date(),

            // relationship
            'user_id' => $user?->id,

            // compliance
            'starter_checklist_recieved_date' => fake()
                ->optional()
                ->date(),

            'starter_form' => fake()
                ->boolean(),

            'tax_code' => fake()
                ->optional()
                ->bothify('TAX-####'),

            'contract_recieved_date' => fake()
                ->optional()
                ->date(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Deal $deal) {

            /*
            |--------------------------------------------------------------------------
            | Attach Company
            |--------------------------------------------------------------------------
            */

            $company = Company::inRandomOrder()
                ->first();

            if ($company) {
                $deal->companies()->attach(
                    $company->id,
                    [
                        'is_primary' => true,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Required Contact
            |--------------------------------------------------------------------------
            */

            $contact = Contact::create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->safeEmail(),
                'phone' => fake()->phoneNumber(),

                // optional fields
                'street_address' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->country(),

                'ni_number' => strtoupper(
                    fake()->bothify('??######?')
                ),

                'bank' => fake()->company(),

                'account_number' => fake()
                    ->numerify('########'),

                'sort_code' => fake()
                    ->numerify('##-##-##'),

                'date_of_birth' => fake()
                    ->date(),

                'marital_status' => fake()
                    ->randomElement([
                        'single',
                        'married',
                        'divorced',
                        'widowed',
                    ]),

                'gender' => fake()
                    ->randomElement([
                        'male',
                        'female',
                        'other',
                    ]),
            ]);

            $deal->contacts()->attach(
                $contact->id,
                [
                    'is_primary' => true,
                ]
            );
        });
    }
}
