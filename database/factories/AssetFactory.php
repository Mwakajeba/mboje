<?php

namespace Database\Factories;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assets\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchaseCost = $this->faker->randomFloat(2, 50000, 500000);
        $salvageValue = $purchaseCost * 0.1; // 10% salvage
        $currentNBV = $purchaseCost * 0.7; // 70% of cost

        return [
            'company_id' => Company::factory(),
            'branch_id' => null,
            'asset_category_id' => AssetCategory::factory(),
            'code' => $this->faker->unique()->bothify('AST-####'),
            'name' => $this->faker->words(3, true),
            'purchase_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'capitalization_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'purchase_cost' => $purchaseCost,
            'salvage_value' => $salvageValue,
            'current_nbv' => $currentNBV,
            'status' => 'active',
            'hfs_status' => 'none',
            'depreciation_stopped' => false,
        ];
    }
}
