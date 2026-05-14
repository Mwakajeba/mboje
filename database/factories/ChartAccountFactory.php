<?php

namespace Database\Factories;

use App\Models\ChartAccount;
use App\Models\AccountClassGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChartAccount>
 */
class ChartAccountFactory extends Factory
{
    protected $model = ChartAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_class_group_id' => AccountClassGroup::factory(),
            'account_code' => $this->faker->unique()->numerify('ACC-####'),
            'account_name' => $this->faker->words(3, true),
            'account_type' => $this->faker->randomElement(['asset', 'liability', 'equity', 'income', 'expense']),
            'parent_id' => null,
            'has_cash_flow' => false,
            'has_equity' => false,
        ];
    }
}
