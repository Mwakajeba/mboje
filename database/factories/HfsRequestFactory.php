<?php

namespace Database\Factories;

use App\Models\Assets\HfsRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assets\HfsRequest>
 */
class HfsRequestFactory extends Factory
{
    protected $model = HfsRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'request_no' => 'HFS-' . now()->format('Y') . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'initiator_id' => User::factory(),
            'status' => 'draft',
            'intended_sale_date' => now()->addMonths(6),
            'expected_close_date' => now()->addMonths(7),
            'buyer_name' => $this->faker->company(),
            'buyer_contact' => $this->faker->phoneNumber(),
            'buyer_address' => $this->faker->address(),
            'justification' => $this->faker->sentence(),
            'expected_costs_to_sell' => $this->faker->randomFloat(2, 1000, 10000),
            'expected_fair_value' => $this->faker->randomFloat(2, 50000, 500000),
            'probability_pct' => $this->faker->numberBetween(75, 95),
            'marketing_actions' => $this->faker->paragraph(),
            'sale_price_range' => $this->faker->randomFloat(2, 40000, 600000),
            'management_committed' => true,
            'management_commitment_date' => now(),
            'exceeds_12_months' => false,
            'is_disposal_group' => false,
            'notes' => $this->faker->paragraph(),
            'attachments' => null,
            'created_by' => User::factory(),
        ];
    }
}
