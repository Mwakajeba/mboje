<?php

namespace Database\Factories;

use App\Models\Assets\AssetCategory;
use App\Models\Company;
use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assets\AssetCategory>
 */
class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

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
            'code' => $this->faker->unique()->bothify('CAT-???'),
            'name' => $this->faker->words(2, true),
            'default_depreciation_method' => 'straight_line',
            'default_useful_life_months' => 60,
            'default_depreciation_rate' => 0,
            'depreciation_convention' => 'monthly_prorata',
            'capitalization_threshold' => 0,
            'asset_account_id' => ChartAccount::factory(),
            'accum_depr_account_id' => ChartAccount::factory(),
            'depr_expense_account_id' => ChartAccount::factory(),
            'hfs_account_id' => ChartAccount::factory(),
        ];
    }
}
