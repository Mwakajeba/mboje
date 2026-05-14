<?php

namespace Database\Factories;

use App\Models\FxRate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FxRate>
 */
class FxRateFactory extends Factory
{
    protected $model = FxRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'rate_date' => now(),
            'from_currency' => 'USD',
            'to_currency' => 'TZS',
            'spot_rate' => $this->faker->randomFloat(6, 2000, 3000),
            'month_end_rate' => null,
            'average_rate' => null,
            'source' => 'manual',
            'is_locked' => false,
            'created_by' => User::factory(),
        ];
    }
}
