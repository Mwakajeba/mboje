<?php

namespace Database\Factories;

use App\Models\Assets\HfsAsset;
use App\Models\Assets\HfsRequest;
use App\Models\Assets\Asset;
use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assets\HfsAsset>
 */
class HfsAssetFactory extends Factory
{
    protected $model = HfsAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hfs_id' => HfsRequest::factory(),
            'asset_id' => Asset::factory(),
            'asset_type' => 'PPE',
            'asset_reference' => null,
            'original_account_id' => ChartAccount::factory(),
            'carrying_amount_at_reclass' => $this->faker->randomFloat(2, 50000, 500000),
            'accumulated_depreciation_at_reclass' => $this->faker->randomFloat(2, 0, 200000),
            'accumulated_impairment_at_reclass' => 0,
            'asset_cost_at_reclass' => $this->faker->randomFloat(2, 100000, 1000000),
            'depreciation_stopped' => false,
            'reclassified_date' => now(),
            'book_currency' => 'USD',
            'book_currency_amount' => $this->faker->randomFloat(2, 50000, 500000),
            'local_currency' => 'TZS',
            'book_local_amount' => $this->faker->randomFloat(2, 50000, 500000),
            'book_fx_rate' => 1.0,
            'current_carrying_amount' => $this->faker->randomFloat(2, 50000, 500000),
            'status' => 'pending_reclass',
            'is_pledged' => false,
            'pledge_details' => null,
            'bank_consent_obtained' => false,
            'bank_consent_date' => null,
            'bank_consent_ref' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }
}
